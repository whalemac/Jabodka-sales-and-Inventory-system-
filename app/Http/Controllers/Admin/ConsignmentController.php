<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\ConsignmentPullout;
use App\Models\ConsignmentReceipt;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Services\StockService;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ConsignmentController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function __construct(protected StockService $stock) {}

    public function index(): View
    {
        $partners = ConsignmentPartner::query()
            ->withCount('items')
            ->orderBy('partner_name')
            ->paginate(9, ['*'], 'ppage');

        $items = ConsignmentItem::query()
            ->with(['partner', 'variant.product', 'pullouts', 'receipt'])
            ->latest('received_at')
            ->paginate(30);

        ConsignmentItem::allocateSoldQuantities($items->getCollection());

        $receipts = ConsignmentReceipt::query()
            ->with(['partner', 'user', 'items.variant.product', 'items.pullouts'])
            ->latest('received_date')
            ->latest('id')
            ->paginate(10, ['*'], 'rpage');
        ConsignmentItem::allocateSoldQuantities($receipts->pluck('items')->flatten());

        $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettledByPartner = SalesItem::query()
            ->with('variant.consignmentItems.partner')
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get()
            ->groupBy(fn (SalesItem $si) => $si->variant->consignmentItems->first()?->partner_id)
            ->map(fn ($group) => [
                'count' => $group->count(),
                'amount' => $group->sum(fn (SalesItem $si) => (float) ($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity),
            ]);

        return view('admin.consignment.index', compact('partners', 'items', 'receipts', 'unsettledByPartner'));
    }

    // ─── Partners ─────────────────────────────────────────────────────────────

    public function createPartner(Request $request): View
    {
        $data = ['partner' => new ConsignmentPartner];

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.consignment._partner-form', $data);
        }

        return view('admin.consignment.partner-form', $data);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $data = $this->validatePartner($request);
        $partner = ConsignmentPartner::query()->create($data);
        ActivityLogger::log('consignment.partner.create', $partner);

        return redirect()->route('admin.consignment.index')->with('status', 'Partner "'.$partner->partner_name.'" added.');
    }

    public function editPartner(Request $request, ConsignmentPartner $partner): View
    {
        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.consignment._partner-form', compact('partner'));
        }

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
            'partner_name' => ['required', 'string', 'max:150'],
            'contact_details' => ['nullable', 'string', 'max:500'],
        ]);
    }

    // ─── Items ────────────────────────────────────────────────────────────────

    public function createItem(Request $request): View
    {
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->sellable()
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->orderBy('product_id')
            ->get();

        $prePartner = $request->get('partner');

        $data = [
            'partners' => $partners,
            'variants' => $variants,
            'prePartner' => $prePartner,
        ];

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.consignment._receipt-form', $data);
        }

        return view('admin.consignment.receipt-form', $data);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:consignment_partners,id'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.units_delivered' => ['required', 'integer', 'min:1'],
            'items.*.agreed_base_price' => ['required', 'numeric', 'min:0'],
            'items.*.shop_markup' => ['required', 'numeric', 'min:0'],
        ]);

        // Only consignment products belong on a partner drop-off; anything else
        // would lose its supplier/invoice attribution.
        $nonConsignmentVariantIds = ProductVariant::query()
            ->whereIn('id', array_column($data['items'], 'variant_id'))
            ->whereHas('product', fn ($q) => $q->where('source_type', '!=', 'consignment'))
            ->pluck('id');

        if ($nonConsignmentVariantIds->isNotEmpty()) {
            return back()
                ->withErrors(['items' => 'Only consignment products can be received on a partner drop-off.'])
                ->withInput();
        }

        $receipt = DB::transaction(function () use ($data) {
            $receipt = ConsignmentReceipt::query()->create([
                'partner_id' => $data['partner_id'],
                'user_id' => auth()->id(),
                'received_date' => $data['received_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $variant = ProductVariant::query()->findOrFail($line['variant_id']);
                $item = ConsignmentItem::query()->create([
                    'consignment_receipt_id' => $receipt->id,
                    'partner_id' => $data['partner_id'],
                    'variant_id' => $variant->id,
                    'units_delivered' => $line['units_delivered'],
                    'agreed_base_price' => $line['agreed_base_price'],
                    'shop_markup' => $line['shop_markup'],
                    'received_at' => $data['received_date'],
                ]);

                $this->stock->increment($variant, (int) $line['units_delivered']);
            }

            ActivityLogger::log('consignment.receipt.create', $receipt, $data['notes'] ?? null, [
                'partner_id' => $data['partner_id'],
                'line_count' => count($data['items']),
            ]);

            return $receipt;
        });

        return redirect()->route('admin.consignment.index')
            ->with('status', 'Consignment drop-off recorded ('.$receipt->items()->count().' lines).');
    }

    public function pulloutForm(Request $request, ConsignmentItem $item): View
    {
        $item->load(['partner', 'variant.product', 'pullouts']);
        ConsignmentItem::allocateSoldQuantities(collect([$item]));

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.consignment._pullout', compact('item'));
        }

        return view('admin.consignment.pullout', compact('item'));
    }

    public function storePullout(Request $request, ConsignmentItem $item): RedirectResponse
    {
        $item->load('pullouts', 'variant.product');
        ConsignmentItem::allocateSoldQuantities(collect([$item]));
        $remaining = $item->remainingQuantity();

        $data = $request->validate([
            'quantity_pulled' => ['required', 'integer', 'min:1', 'max:'.$remaining],
            'pullout_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $data['reason'] ?? null;

        try {
            DB::transaction(function () use ($item, $data, $reason) {
                $qty = (int) $data['quantity_pulled'];

                ConsignmentPullout::query()->create([
                    'consignment_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'quantity_pulled' => $qty,
                    'pullout_date' => $data['pullout_date'],
                    'reason' => $reason,
                ]);

                $this->stock->adjust(
                    variant: $item->variant,
                    user: auth()->user(),
                    quantityChanged: -$qty,
                    type: 'consignment_return',
                    reason: $reason ?: 'Consignment pull-out',
                );

                ActivityLogger::log('consignment.pullout', $item, $reason, [
                    'quantity_pulled' => $qty,
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity_pulled' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.consignment.index')
            ->with('status', 'Pulled out '.$data['quantity_pulled'].' of '.$item->variant->displayName().'.');
    }

    public function editItem(Request $request, ConsignmentItem $item): View
    {
        $item->load(['partner', 'variant.product']);
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->orderBy('product_id')
            ->get();

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.consignment._item-form', compact('item', 'partners', 'variants'));
        }

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
            'partner_id' => ['required', 'exists:consignment_partners,id'],
            'variant_id' => ['required', 'exists:product_variants,id'],
            'units_delivered' => ['required', 'integer', 'min:1'],
            'agreed_base_price' => ['required', 'numeric', 'min:0'],
            'shop_markup' => ['required', 'numeric', 'min:0'],
            'received_at' => ['required', 'date'],
        ]);
    }

    // ─── Payouts ──────────────────────────────────────────────────────────────

    public function payouts(Request $request): View
    {
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $payments = ConsignmentPayment::query()
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
        $grouped = $unsettledRows->groupBy(function (SalesItem $si) {
            return $si->variant->consignmentItems->first()?->partner_id ?? 0;
        });
        $unsettledCounts = $grouped->map(fn ($items) => $items->count());

        // Amount owed per partner (partner_id => pesos). Same agreed_base_price
        // maths as generatePayout() so the KPI cards always match a payout total.
        $unsettledAmounts = $grouped->map(fn ($items) => $items->sum(
            fn (SalesItem $si) => (float) ($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity
        ));

        $page = max(1, (int) $request->input('upage', 1));
        $perPage = 5;
        $unsettled = new LengthAwarePaginator(
            $grouped->forPage($page, $perPage),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $unsettled->setPageName('upage');

        return view('admin.consignment.payouts', compact('partners', 'payments', 'unsettled', 'unsettledCounts', 'unsettledAmounts'));
    }

    public function generatePayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:consignment_partners,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $partnerId = (int) $data['partner_id'];
        $periodStart = $data['period_start'];
        $periodEndDate = $data['period_end'];           // raw date — stored in DB
        $periodEnd = $periodEndDate.' 23:59:59';     // with time — used for query range

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
                'partner_id' => $partnerId,
                'user_id' => auth()->id(),
                'period_start' => $periodStart,
                'period_end' => $periodEndDate,
                'total_amount' => $total,
                'payment_status' => 'pending',
            ]);

            foreach ($salesItems as $item) {
                $ci = $item->variant->consignmentItems->first();
                if (! $ci) {
                    continue;
                }

                ConsignmentPaymentItem::query()->create([
                    'consignment_payment_id' => $payment->id,
                    'sales_item_id' => $item->id,
                    'consignment_item_id' => $ci->id,
                    'quantity' => $item->quantity,
                    'partner_amount' => (float) $item->quantity * (float) $ci->agreed_base_price,
                ]);
            }

            ActivityLogger::log('consignment.payout.generate', $payment, null, [
                'partner_id' => $partnerId,
                'total_amount' => $total,
                'item_count' => $salesItems->count(),
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

        $lines = ConsignmentPaymentItem::query()
            ->where('consignment_payment_id', $payment->id)
            ->with(['consignmentItem.variant.product', 'salesItem.transaction'])
            ->orderBy('id')
            ->paginate(15);

        return view('admin.consignment.payout-show', compact('payment', 'lines'));
    }

    public function markPaid(Request $request, ConsignmentPayment $payment): RedirectResponse
    {
        $data = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:80'],
        ]);

        $payment->update([
            'payment_status' => 'paid',
            'reference_number' => $data['reference_number'] ?? null,
            'payment_date' => now(),
        ]);

        ActivityLogger::log('consignment.payout.paid', $payment, null, $data);

        return back()->with('status', 'Payout marked as paid.');
    }
}
