<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTransaction;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', '');

        $orders = SalesTransaction::query()
            ->with(['items.variant.product', 'customer', 'payments'])
            ->where('channel', 'online')
            ->when($status !== '', fn ($q) => $q->where('shipment_status', $status))
            ->latest('transaction_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.online.index', compact('orders'));
    }

    public function create(): View
    {
        return view('admin.online.create');
    }

    public function show(SalesTransaction $transaction): View
    {
        abort_unless($transaction->channel === 'online', 404);

        $transaction->load([
            'items.variant.product',
            'customer',
            'payments',
            'receipt',
            'user',
        ]);

        return view('admin.online.show', compact('transaction'));
    }

    public function store(Request $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'customer_name' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'courier' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'in:cash,gcash'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'confirm_payment' => ['sometimes', 'boolean'],
        ]);

        try {
            $sales->checkout(auth()->user(), 'online', $data['items'], [
                'customer_name' => $data['customer_name'],
                'contact_number' => $data['contact_number'],
                'shipping_address' => $data['shipping_address'],
                'landmark' => $data['landmark'] ?? null,
                'buyer_type' => 'online',
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'courier' => $data['courier'] ?? 'J&T Express',
                'payment_method' => $data['payment_method'] ?? null,
                'amount_paid' => $data['amount_paid'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'confirm_payment' => $request->boolean('confirm_payment'),
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.online.index')->with('status', 'Online order recorded.');
    }

    public function updateShipment(Request $request, SalesTransaction $transaction): RedirectResponse
    {
        abort_unless($transaction->channel === 'online', 404);

        $data = $request->validate([
            'shipment_status' => ['required', 'in:pending_payment,ready_to_ship,shipped,delivered,cancelled'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'courier' => ['nullable', 'string', 'max:50'],
        ]);

        if (in_array($data['shipment_status'], ['ready_to_ship', 'shipped'], true) && ! $transaction->isPaid()) {
            return back()->withErrors([
                'shipment_status' => 'Payment must be confirmed before this order is ready to ship.',
            ]);
        }

        $transaction->update($data);

        return back()->with('status', 'Shipment updated.');
    }
}
