<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'variant_id',
        'quantity',
        'price_at_sale',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_at_sale' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'transaction_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class, 'sales_item_id');
    }

    public function lineTotal(): float
    {
        return (float) $this->quantity * (float) $this->price_at_sale;
    }

    public function hasReturn(): bool
    {
        return $this->returns()->exists();
    }
}
