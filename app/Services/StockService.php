<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockAdjustment;
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
