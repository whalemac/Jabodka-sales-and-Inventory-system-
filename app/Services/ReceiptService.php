<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Support\ActivityLogger;

class ReceiptService
{
    public function issue(SalesTransaction $transaction, User $user): Receipt
    {
        if ($transaction->receipt) {
            return $this->reprint($transaction->receipt);
        }

        $receipt = Receipt::query()->create([
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'receipt_number' => $this->nextNumber(),
            'issued_at' => now(),
            'reprint_count' => 0,
            'last_printed_at' => now(),
        ]);

        ActivityLogger::log('receipt.issue', $receipt, null, [
            'receipt_number' => $receipt->receipt_number,
        ], $user);

        return $receipt;
    }

    public function reprint(Receipt $receipt): Receipt
    {
        $receipt->increment('reprint_count');
        $receipt->update(['last_printed_at' => now()]);

        ActivityLogger::log('receipt.reprint', $receipt, 'Reprint requested');

        return $receipt->fresh();
    }

    protected function nextNumber(): string
    {
        $year = now()->year;
        $prefix = 'JBK-'.$year.'-';

        $last = Receipt::query()
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('receipt_number');

        $seq = 1;
        if ($last) {
            $seq = ((int) substr($last, -6)) + 1;
        }

        return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
