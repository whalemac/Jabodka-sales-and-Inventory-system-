<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialTransaction extends Model
{
    protected $fillable = [
        'material_id',
        'production_id',
        'user_id',
        'transaction_type',
        'quantity',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'material_id');
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(ProductionLog::class, 'production_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
