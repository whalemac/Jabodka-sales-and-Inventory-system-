<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentItem extends Model
{
    protected $fillable = [
        'partner_id',
        'variant_id',
        'units_delivered',
        'agreed_base_price',
        'shop_markup',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'units_delivered' => 'integer',
            'agreed_base_price' => 'decimal:2',
            'shop_markup' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ConsignmentPartner::class, 'partner_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(ConsignmentPaymentItem::class);
    }

    public function sellingPrice(): float
    {
        return (float) $this->agreed_base_price + (float) $this->shop_markup;
    }
}
