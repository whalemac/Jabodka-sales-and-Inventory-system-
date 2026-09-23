<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected SaleService $sales) {}

    /**
     * Confirm a payment for a transaction (used for online orders paid after entry).
     */
    public function confirm(Request $request, SalesTransaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'payment_method'   => ['required', 'in:cash,gcash'],
            'amount_paid'      => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:80'],
        ]);

        $this->sales->confirmPayment($transaction, auth()->user(), $data);

        return back()->with('status', 'Payment confirmed.');
    }
}
