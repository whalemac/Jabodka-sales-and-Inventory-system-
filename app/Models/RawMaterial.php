<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    protected $fillable = [
        'material_name',
        'unit',
        'stock_quantity',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:2',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class, 'material_id');
    }
}
