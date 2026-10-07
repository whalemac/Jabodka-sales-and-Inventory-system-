<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory\CustomerFactory */
    use HasFactory;

    protected $fillable = [
        'name',
        'buyer_type',
        'contact_number',
        'shipping_address',
        'landmark',
    ];

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }
}
