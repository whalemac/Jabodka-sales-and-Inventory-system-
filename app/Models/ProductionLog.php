<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLog extends Model
{
    protected $fillable = [
        'variant_id',
        'user_id',
        'quantity_produced',
        'production_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_produced' => 'integer',
            'production_date' => 'date',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function materialTransactions(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class, 'production_id');
    }
}
