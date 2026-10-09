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
                'shipment_status' => $channel === 'online' ? 'pending' : null,
            ]);

            foreach ($lines as $line) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->sellable()
                    ->find($line['variant_id']);

                if (! $variant) {
                    throw new RuntimeException(
                        'One of the selected products is no longer available for sale. Refresh and try again.'
                    );
                }

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

                // Payment and shipment statuses move independently.
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

            ActivityLogger::log('payment.confirm', $payment, null, $data, $user);

            return $payment;
        });
    }

    public function setPaymentStatus(SalesTransaction $transaction, User $user, string $status, ?string $reference = null): string
    {
        return DB::transaction(function () use ($transaction, $user, $status, $reference) {
            $transaction->load(['items', 'payments']);
            $old = $transaction->derivedPaymentStatus();

            if ($status === 'confirmed') {
                $pending = $transaction->payments->firstWhere('payment_status', 'pending');
                if ($pending) {
                    $pending->update(array_filter([
                        'payment_status' => 'confirmed',
                        'payment_date' => now(),
                        'reference_number' => $reference ?? $pending->reference_number,
                    ], fn ($v) => $v !== null));
                } elseif (! $transaction->isPaid()) {
                    $due = max(0.01, $transaction->grandTotal() - $transaction->amountPaid());
                    Payment::query()->create([
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                        'payment_method' => $transaction->payments->first()?->payment_method ?? 'gcash',
                        'amount_paid' => $due,
                        'payment_status' => 'confirmed',
                        'payment_date' => now(),
                        'reference_number' => $reference,
                    ]);
                } elseif ($reference) {
                    // Already paid but a reference was supplied — stamp it on the latest confirmed payment.
                    $confirmed = $transaction->payments->where('payment_status', 'confirmed')->last();
                    if ($confirmed && ! $confirmed->reference_number) {
                        $confirmed->update(['reference_number' => $reference]);
                    }
                }
            } elseif ($status === 'refunded') {
                Payment::query()
                    ->where('transaction_id', $transaction->id)
                    ->where('payment_status', 'confirmed')
                    ->update(['payment_status' => 'refunded']);
            } elseif ($status === 'pending') {
                Payment::query()
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('payment_status', ['confirmed', 'refunded'])
                    ->update(['payment_status' => 'pending', 'payment_date' => null]);
            }

            $transaction->unsetRelation('payments');
            $transaction->load('payments');
            $new = $transaction->derivedPaymentStatus();

            ActivityLogger::log('order.payment.status', $transaction, null, [
                'from' => $old,
                'to' => $new,
            ], $user);

            return $new;
        });
    }
}
