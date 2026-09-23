<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'receipt_number',
        'issued_at',
        'reprint_count',
        'last_printed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'last_printed_at' => 'datetime',
            'reprint_count' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
