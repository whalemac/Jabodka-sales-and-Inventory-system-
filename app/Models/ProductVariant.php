<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'version',
        'stock_count',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'stock_count' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'variant_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'variant_id');
    }

    public function consignmentItems(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class, 'variant_id');
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class, 'variant_id');
    }

    public function isLowStock(): bool
    {
        return $this->stock_count <= $this->reorder_level;
    }

    public function label(): string
    {
        $parts = array_filter([$this->size, $this->version]);

        return $parts ? implode(' / ', $parts) : 'Standard';
    }

    public function displayName(): string
    {
        return trim($this->product?->name.' — '.$this->label());
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_count', '<=', 'reorder_level');
    }
}
