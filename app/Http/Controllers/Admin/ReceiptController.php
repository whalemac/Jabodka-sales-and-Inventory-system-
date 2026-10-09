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
        return view('admin.receipts.show', ['receipt' => $this->load($receipt)]);
    }

    /**
     * Receipt fragment rendered inside a modal (A5) — never a full navigation.
     */
    public function partial(Receipt $receipt): View
    {
        return view('admin.receipts._fragment', ['receipt' => $this->load($receipt)]);
    }

    protected function load(Receipt $receipt): Receipt
    {
        return $receipt->load([
            'transaction.items.variant.product',
            'transaction.customer',
            'transaction.payments',
            'user',
        ]);
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
