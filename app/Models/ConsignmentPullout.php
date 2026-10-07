<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentPullout extends Model
{
    protected $fillable = [
        'consignment_item_id',
        'user_id',
        'quantity_pulled',
        'pullout_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity_pulled' => 'integer',
            'pullout_date' => 'date',
        ];
    }

    public function consignmentItem(): BelongsTo
    {
        return $this->belongsTo(ConsignmentItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
