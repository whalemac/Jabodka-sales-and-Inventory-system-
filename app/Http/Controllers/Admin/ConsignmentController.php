<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $partners = ConsignmentPartner::query()
            ->withCount('items')
            ->orderBy('partner_name')
            ->get();

        $items = ConsignmentItem::query()
            ->with(['partner', 'variant.product'])
            ->latest('received_at')
            ->paginate(30);

        // Quick unsettled summary per partner for the index page
        $settledIds    = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettledByPartner = SalesItem::query()
            ->with('variant.consignmentItems.partner')
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get()
            ->groupBy(fn (SalesItem $si) => $si->variant->consignmentItems->first()?->partner_id)
            ->map(fn ($group) => [
                'count'  => $group->count(),
                'amount' => $group->sum(fn (SalesItem $si) => (float) ($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity),
            ]);

        return view('admin.consignment.index', compact('partners', 'items', 'unsettledByPartner'));
    }

    // ─── Partners ─────────────────────────────────────────────────────────────

    public function createPartner(): View
    {
        return view('admin.consignment.partner-form', ['partner' => new ConsignmentPartner]);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $data    = $this->validatePartner($request);
        $partner = ConsignmentPartner::query()->create($data);
        ActivityLogger::log('consignment.partner.create', $partner);

        return redirect()->route('admin.consignment.index')->with('status', 'Partner "'.$partner->partner_name.'" added.');
    }

    public function editPartner(ConsignmentPartner $partner): View
    {
        return view('admin.consignment.partner-form', compact('partner'));
    }

    public function updatePartner(Request $request, ConsignmentPartner $partner): RedirectResponse
    {
        $partner->update($this->validatePartner($request));
        ActivityLogger::log('consignment.partner.update', $partner);

        return redirect()->route('admin.consignment.index')->with('status', 'Partner updated.');
    }

    protected function validatePartner(Request $request): array
    {
        return $request->validate([
            'partner_name'    => ['required', 'string', 'max:150'],
            'contact_details' => ['nullable', 'string', 'max:500'],
        ]);
    }

    // ─── Items ────────────────────────────────────────────────────────────────

    public function createItem(Request $request): View
    {
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->orderBy('product_id')
            ->get();

        $prePartner = $request->get('partner');

        return view('admin.consignment.item-form', [
            'item'       => new ConsignmentItem,
            'partners'   => $partners,
            'variants'   => $variants,
            'prePartner' => $prePartner,
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $item = ConsignmentItem::query()->create($data);
        ActivityLogger::log('consignment.item.create', $item);

        return redirect()->route('admin.consignment.index')->with('status', 'Consignment item recorded.');
    }

    public function editItem(ConsignmentItem $item): View
    {
        $item->load(['partner', 'variant.product']);
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->orderBy('product_id')
            ->get();

        return view('admin.consignment.item-form', compact('item', 'partners', 'variants'));
    }

    public function updateItem(Request $request, ConsignmentItem $item): RedirectResponse
    {
        $item->update($this->validateItem($request, $item));
        ActivityLogger::log('consignment.item.update', $item);

        return redirect()->route('admin.consignment.index')->with('status', 'Consignment item updated.');
    }

    protected function validateItem(Request $request, ?ConsignmentItem $item = null): array
    {
        return $request->validate([
            'partner_id'        => ['required', 'exists:consignment_partners,id'],
            'variant_id'        => ['required', 'exists:product_variants,id'],
            'units_delivered'   => ['required', 'integer', 'min:1'],
            'agreed_base_price' => ['required', 'numeric', 'min:0'],
            'shop_markup'       => ['required', 'numeric', 'min:0'],
            'received_at'       => ['required', 'date'],
        ]);
    }

    // ─── Payouts ──────────────────────────────────────────────────────────────

    public function payouts(Request $request): View
    {
        $partners  = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $payments  = ConsignmentPayment::query()
            ->with('partner')
            ->withCount('items')
            ->latest()
            ->paginate(20);

        // All unsettled consignment sales, grouped by partner_id
        $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettledRows = SalesItem::query()
            ->with([
                'variant.product',
                'variant.consignmentItems' => fn ($q) => $q->orderByDesc('received_at'),
                'transaction',
            ])
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get();

        // Group by partner, keyed by partner_id (int)
        $unsettled = $unsettledRows->groupBy(function (SalesItem $si) {
            return $si->variant->consignmentItems->first()?->partner_id ?? 0;
        });

        return view('admin.consignment.payouts', compact('partners', 'payments', 'unsettled'));
    }

    public function generatePayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id'   => ['required', 'exists:consignment_partners,id'],
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $partnerId    = (int) $data['partner_id'];
        $periodStart  = $data['period_start'];
        $periodEndDate = $data['period_end'];           // raw date — stored in DB
        $periodEnd    = $periodEndDate.' 23:59:59';     // with time — used for query range

        return DB::transaction(function () use ($partnerId, $periodStart, $periodEnd, $periodEndDate) {
            $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');

            // Unsettled consignment sales for this partner within the period
            $salesItems = SalesItem::query()
                ->with(['variant.consignmentItems' => fn ($q) => $q->where('partner_id', $partnerId)])
                ->whereNotIn('id', $settledIds)
                ->whereHas('variant.consignmentItems', fn ($q) => $q->where('partner_id', $partnerId))
                ->whereHas('transaction', fn ($q) => $q
                    ->whereBetween('transaction_date', [$periodStart, $periodEnd])
                )
                ->get();

            if ($salesItems->isEmpty()) {
                return back()->withErrors([
                    'partner_id' => 'No unsettled sales for this partner within the selected period.',
                ]);
            }

            // Calculate total: SUM(quantity × agreed_base_price)
            $total = $salesItems->sum(function (SalesItem $item) {
                $ci = $item->variant->consignmentItems->first();

                return $ci ? (float) $item->quantity * (float) $ci->agreed_base_price : 0;
            });

            $payment = ConsignmentPayment::query()->create([
                'partner_id'     => $partnerId,
                'user_id'        => auth()->id(),
                'period_start'   => $periodStart,
                'period_end'     => $periodEndDate,
                'total_amount'   => $total,
                'payment_status' => 'pending',
            ]);

            foreach ($salesItems as $item) {
                $ci = $item->variant->consignmentItems->first();
                if (! $ci) {
                    continue;
                }

                ConsignmentPaymentItem::query()->create([
                    'consignment_payment_id' => $payment->id,
                    'sales_item_id'          => $item->id,
                    'consignment_item_id'    => $ci->id,
                    'quantity'               => $item->quantity,
                    'partner_amount'         => (float) $item->quantity * (float) $ci->agreed_base_price,
                ]);
            }

            ActivityLogger::log('consignment.payout.generate', $payment, null, [
                'partner_id'   => $partnerId,
                'total_amount' => $total,
                'item_count'   => $salesItems->count(),
            ]);

            return redirect()
                ->route('admin.consignment.payouts.show', $payment)
                ->with('status', 'Payout generated: ₱'.number_format($total, 2).'. Review the breakdown below.');
        });
    }

    public function showPayout(ConsignmentPayment $payment): View
    {
        $payment->load([
            'partner',
            'user',
            'items.consignmentItem.variant.product',
            'items.salesItem.transaction',
        ]);

        return view('admin.consignment.payout-show', compact('payment'));
    }

    public function markPaid(Request $request, ConsignmentPayment $payment): RedirectResponse
    {
        $data = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:80'],
        ]);

        $payment->update([
            'payment_status'   => 'paid',
            'reference_number' => $data['reference_number'] ?? null,
            'payment_date'     => now(),
        ]);

        ActivityLogger::log('consignment.payout.paid', $payment, null, $data);

        return back()->with('status', 'Payout marked as paid.');
    }
}
