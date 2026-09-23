<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentPaymentItem extends Model
{
    protected $fillable = [
        'consignment_payment_id',
        'sales_item_id',
        'consignment_item_id',
        'quantity',
        'partner_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'partner_amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ConsignmentPayment::class, 'consignment_payment_id');
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class);
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(ConsignmentItem::class);
    }
}
