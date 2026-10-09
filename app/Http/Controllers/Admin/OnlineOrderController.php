<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOnlineOrderRequest;
use App\Http\Requests\Admin\UpdatePaymentStatusRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use App\Models\SalesTransaction;
use App\Services\SaleService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    /**
     * Order invoice fragment rendered inside a modal (A5) — never a full navigation.
     */
    public function invoice(SalesTransaction $transaction): View
    {
        abort_unless($transaction->channel === 'online', 404);

        $transaction->load([
            'items.variant.product',
            'customer',
            'payments',
            'user',
        ]);

        return view('admin.online._invoice', compact('transaction'));
    }

    public function store(StoreOnlineOrderRequest $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validated();

        // A confirmed GCash reference auto-advances payment to Confirmed (D).
        $confirm = $request->boolean('confirm_payment');
        if (($data['payment_method'] ?? null) === 'gcash' && filled($data['payment_reference'] ?? null)) {
            $confirm = true;
        }

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
                'confirm_payment' => $confirm,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.online.index')->with('status', 'Online order recorded.');
    }

    public function updateShipment(UpdateShipmentRequest $request, SalesTransaction $transaction): RedirectResponse|JsonResponse
    {
        abort_unless($transaction->channel === 'online', 404);

        $data = $request->validated();
        $current = $transaction->shipment_status;

        // Default to current status when only tracking/courier is being saved.
        $target = $data['shipment_status'] ?? $current;
        $newTracking = trim((string) ($data['tracking_number'] ?? ''));
        $existingTracking = trim((string) ($transaction->tracking_number ?? ''));

        // Entering a tracking number auto-advances Pending/Preparing to Shipped.
        // Covers both "save with next step" and "save tracking only" flows —
        // once a tracking number exists the order is, by definition, shipped.
        if (($newTracking !== '' || ($target === $current && $existingTracking !== ''))
            && in_array($current, ['pending', 'preparing'], true)
            && in_array($target, ['pending', 'preparing', $current], true)) {
            $target = 'shipped';
            $data['shipment_status'] = 'shipped';
        }

        $this->assertAllowedTransition(
            'shipment_status',
            $current,
            $target,
            SalesTransaction::SHIPMENT_TRANSITIONS,
            SalesTransaction::SHIPMENT_STATUSES,
        );

        $old = $current;
        $payload = ['shipment_status' => $target];
        if (array_key_exists('tracking_number', $data) && $data['tracking_number'] !== null) {
            $payload['tracking_number'] = $data['tracking_number'];
        }
        if (array_key_exists('courier', $data) && $data['courier'] !== null) {
            $payload['courier'] = $data['courier'];
        }

        $transaction->update($payload);

        ActivityLogger::log('order.shipment.update', $transaction, null, [
            'from' => $old,
            'to' => $target,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'shipment_status' => $transaction->shipment_status,
                'label' => SalesTransaction::SHIPMENT_STATUSES[$transaction->shipment_status] ?? $transaction->shipment_status,
            ]);
        }

        return back()->with('status', 'Shipment updated.');
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, SalesTransaction $transaction, SaleService $sales): RedirectResponse|JsonResponse
    {
        abort_unless($transaction->channel === 'online', 404);

        $data = $request->validated();
        $current = $transaction->derivedPaymentStatus();

        // Entering a GCash/payment reference number auto-confirms a pending payment —
        // a reference is proof of payment, so the order moves to Confirmed.
        $reference = trim((string) ($data['reference_number'] ?? ''));
        if ($reference !== '' && $current === 'pending') {
            $data['payment_status'] = 'confirmed';
        }

        $this->assertAllowedTransition(
            'payment_status',
            $current,
            $data['payment_status'],
            SalesTransaction::PAYMENT_TRANSITIONS,
            SalesTransaction::PAYMENT_STATUSES,
        );

        $status = $sales->setPaymentStatus($transaction, auth()->user(), $data['payment_status'], $reference !== '' ? $reference : null);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'payment_status' => $status,
                'label' => SalesTransaction::PAYMENT_STATUSES[$status] ?? $status,
            ]);
        }

        return back()->with('status', 'Payment status updated.');
    }

    /**
     * Server-side one-way state machine guard (A4). Rejected moves return 422,
     * which the JSON endpoints surface inline and web forms surface as errors.
     */
    protected function assertAllowedTransition(
        string $attribute,
        ?string $current,
        string $target,
        array $transitions,
        array $labels,
    ): void {
        $allowed = isset($current) ? ($transitions[$current] ?? []) : [];

        if ($current !== $target && ! in_array($target, $allowed, true)) {
            $from = $current !== null ? ($labels[$current] ?? $current) : 'none';
            $to = $labels[$target] ?? $target;

            throw ValidationException::withMessages([
                $attribute => "{$to} is not a valid next status from {$from}.",
            ]);
        }
    }
}
