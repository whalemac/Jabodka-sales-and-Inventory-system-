<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesTransaction extends Model
{
    /** @use HasFactory\SalesTransactionFactory */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'channel',
        'transaction_date',
        'shipping_fee',
        'courier',
        'tracking_number',
        'shipment_status',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'shipping_fee' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class, 'transaction_id');
    }

    public function itemsTotal(): float
    {
        return (float) $this->items->sum(fn (SalesItem $item) => $item->quantity * $item->price_at_sale);
    }

    public function grandTotal(): float
    {
        return $this->itemsTotal() + (float) ($this->shipping_fee ?? 0);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments
            ->where('payment_status', 'confirmed')
            ->sum('amount_paid');
    }

    public function isPaid(): bool
    {
        return $this->amountPaid() >= $this->grandTotal() && $this->grandTotal() > 0;
    }

    public const SHIPMENT_STATUSES = [
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered',
        'returned' => 'Returned',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'refunded' => 'Refunded',
    ];

    public function derivedPaymentStatus(): string
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        if ($payments->contains(fn (Payment $p) => $p->payment_status === 'refunded')
            && ! $payments->contains(fn (Payment $p) => $p->payment_status === 'confirmed')) {
            return 'refunded';
        }

        if ($this->isPaid()) {
            return 'confirmed';
        }

        if ($payments->contains(fn (Payment $p) => $p->payment_status === 'confirmed')) {
            return 'confirmed';
        }

        return 'pending';
    }
}
