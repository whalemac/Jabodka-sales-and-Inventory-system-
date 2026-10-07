<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function adjust(
        ProductVariant $variant,
        User $user,
        int $quantityChanged,
        string $type,
        string $reason,
        ?int $supplierId = null,
    ): StockAdjustment {
        if ($quantityChanged === 0) {
            throw new RuntimeException('Quantity changed cannot be zero.');
        }

        return DB::transaction(function () use ($variant, $user, $quantityChanged, $type, $reason, $supplierId) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $newCount = $locked->stock_count + $quantityChanged;

            if ($newCount < 0) {
                throw new RuntimeException('Stock cannot go below zero for '.$locked->displayName().'.');
            }

            $locked->update(['stock_count' => $newCount]);

            $adjustment = StockAdjustment::query()->create([
                'variant_id' => $locked->id,
                'user_id' => $user->id,
                'supplier_id' => $type === 'restock' ? $supplierId : null,
                'adjustment_type' => $type,
                'quantity_changed' => $quantityChanged,
                'reason' => $reason,
            ]);

            ActivityLogger::log('stock.adjust', $adjustment, $reason, [
                'variant_id' => $locked->id,
                'quantity_changed' => $quantityChanged,
                'type' => $type,
            ], $user);

            return $adjustment;
        });
    }

    /**
     * Record a supplier invoice: one receipt header + many line items.
     * This is the only path that should restock from a supplier delivery.
     *
     * @param  array<int, array{variant_id:int, quantity_received:int, unit_cost?:float|null}>  $lines
     */
    public function receiveInvoice(
        User $user,
        string $invoiceNumber,
        string $receivedDate,
        array $lines,
        ?int $supplierId = null,
        ?string $notes = null,
    ): StockReceipt {
        if ($lines === []) {
            throw new RuntimeException('Add at least one product line.');
        }

        return DB::transaction(function () use ($user, $invoiceNumber, $receivedDate, $lines, $supplierId, $notes) {
            $receipt = StockReceipt::query()->create([
                'supplier_id' => $supplierId,
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber,
                'received_date' => $receivedDate,
                'notes' => $notes,
                'total_amount' => 0,
            ]);

            $total = 0.0;

            foreach ($lines as $line) {
                $variant = ProductVariant::query()->lockForUpdate()->findOrFail($line['variant_id']);
                $qty = (int) $line['quantity_received'];
                $unitCost = isset($line['unit_cost']) && $line['unit_cost'] !== '' && $line['unit_cost'] !== null
                    ? (float) $line['unit_cost']
                    : null;
                $lineTotal = $unitCost !== null ? round($unitCost * $qty, 2) : null;

                StockReceiptItem::query()->create([
                    'stock_receipt_id' => $receipt->id,
                    'variant_id' => $variant->id,
                    'quantity_received' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $variant->increment('stock_count', $qty);
                $total += $lineTotal ?? 0;
            }

            $receipt->update(['total_amount' => $total]);

            ActivityLogger::log('stock.receive', $receipt, $notes, [
                'invoice_number' => $invoiceNumber,
                'line_count' => count($lines),
                'total_amount' => $total,
            ], $user);

            return $receipt->load('items.variant.product');
        });
    }

    public function decrementForSale(ProductVariant $variant, int $quantity): void
    {
        $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

        if ($locked->stock_count < $quantity) {
            throw new RuntimeException('Not enough stock for '.$locked->displayName().'.');
        }

        $locked->decrement('stock_count', $quantity);
    }

    public function increment(ProductVariant $variant, int $quantity): void
    {
        $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
        $locked->increment('stock_count', $quantity);
    }
}
