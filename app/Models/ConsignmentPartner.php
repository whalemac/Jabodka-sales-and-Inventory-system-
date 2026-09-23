<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentPartner extends Model
{
    protected $fillable = [
        'partner_name',
        'contact_details',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class, 'partner_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ConsignmentPayment::class, 'partner_id');
    }
}
