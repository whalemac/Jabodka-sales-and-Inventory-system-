<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\SalesTransaction;
use App\Services\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __construct(protected ReceiptService $receipts) {}

    public function show(Receipt $receipt): View
    {
        $receipt->load([
            'transaction.items.variant.product',
            'transaction.customer',
            'transaction.payments',
            'user',
        ]);

        return view('admin.receipts.show', compact('receipt'));
    }

    public function issue(SalesTransaction $transaction): RedirectResponse
    {
        $receipt = $this->receipts->issue($transaction, auth()->user());

        return redirect()
            ->route('admin.receipts.show', $receipt)
            ->with('status', 'Receipt issued: '.$receipt->receipt_number);
    }

    public function reprint(Receipt $receipt): RedirectResponse
    {
        $receipt = $this->receipts->reprint($receipt);

        return redirect()
            ->route('admin.receipts.show', $receipt)
            ->with('status', 'Reprint recorded ('.$receipt->reprint_count.'× reprinted).');
    }
}
