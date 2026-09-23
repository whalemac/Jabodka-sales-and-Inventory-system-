<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'sales_item_id',
        'user_id',
        'return_type',
        'reason',
        'refund_amount',
        'return_date',
        'restocked',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'return_date' => 'datetime',
            'restocked' => 'boolean',
        ];
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class, 'sales_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
