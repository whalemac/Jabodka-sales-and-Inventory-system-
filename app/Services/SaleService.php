<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        protected StockService $stock,
        protected ReceiptService $receipts,
    ) {}

    /**
     * @param  array<int, array{variant_id:int, quantity:int, price?:float}>  $lines
     */
    public function checkout(
        User $user,
        string $channel,
        array $lines,
        array $options = [],
    ): SalesTransaction {
        if ($lines === []) {
            throw new RuntimeException('Add at least one item.');
        }

        return DB::transaction(function () use ($user, $channel, $lines, $options) {
            $customerId = $options['customer_id'] ?? null;

            if ($channel === 'online') {
                $customer = Customer::query()->create([
                    'name' => $options['customer_name'] ?? null,
                    'buyer_type' => $options['buyer_type'] ?? 'online',
                    'contact_number' => $options['contact_number'] ?? null,
                    'shipping_address' => $options['shipping_address'] ?? null,
                    'landmark' => $options['landmark'] ?? null,
                ]);
                $customerId = $customer->id;
            } elseif (! empty($options['walk_in_name']) || ! empty($options['contact_number'])) {
                $customer = Customer::query()->create([
                    'name' => $options['walk_in_name'] ?? 'Walk-in',
                    'buyer_type' => 'walk_in',
                    'contact_number' => $options['contact_number'] ?? null,
                ]);
                $customerId = $customer->id;
            }

            $transaction = SalesTransaction::query()->create([
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'channel' => $channel,
                'transaction_date' => now(),
                'shipping_fee' => $options['shipping_fee'] ?? null,
                'courier' => $options['courier'] ?? ($channel === 'online' ? 'J&T Express' : null),
                'tracking_number' => $options['tracking_number'] ?? null,
                'shipment_status' => $channel === 'online' ? 'pending_payment' : null,
            ]);

            foreach ($lines as $line) {
                $variant = ProductVariant::query()->with('product')->findOrFail($line['variant_id']);
                $qty = (int) $line['quantity'];
                $price = isset($line['price']) ? (float) $line['price'] : (float) $variant->product->base_price;

                $this->stock->decrementForSale($variant, $qty);

                SalesItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'variant_id' => $variant->id,
                    'quantity' => $qty,
                    'price_at_sale' => $price,
                ]);
            }

            if (! empty($options['payment_method']) && ! empty($options['amount_paid'])) {
                $status = ($options['confirm_payment'] ?? true) ? 'confirmed' : 'pending';
                Payment::query()->create([
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'payment_method' => $options['payment_method'],
                    'amount_paid' => $options['amount_paid'],
                    'payment_status' => $status,
                    'payment_date' => $status === 'confirmed' ? now() : null,
                    'reference_number' => $options['payment_reference'] ?? null,
                ]);

                if ($channel === 'online' && $status === 'confirmed') {
                    $transaction->update(['shipment_status' => 'ready_to_ship']);
                }
            }

            if (! empty($options['issue_receipt'])) {
                $transaction->load('items');
                $this->receipts->issue($transaction, $user);
            }

            ActivityLogger::log('sale.create', $transaction, $options['reason'] ?? null, [
                'channel' => $channel,
                'item_count' => count($lines),
            ], $user);

            return $transaction->load(['items.variant.product', 'customer', 'payments', 'receipt', 'user']);
        });
    }

    public function confirmPayment(SalesTransaction $transaction, User $user, array $data): Payment
    {
        return DB::transaction(function () use ($transaction, $user, $data) {
            $payment = Payment::query()->create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'payment_status' => 'confirmed',
                'payment_date' => now(),
                'reference_number' => $data['reference_number'] ?? null,
            ]);

            $transaction->load(['items', 'payments']);

            if ($transaction->channel === 'online' && $transaction->isPaid()) {
                $transaction->update(['shipment_status' => 'ready_to_ship']);
            }

            ActivityLogger::log('payment.confirm', $payment, null, $data, $user);

            return $payment;
        });
    }
}
