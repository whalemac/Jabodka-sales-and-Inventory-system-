<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    public function index(): View
    {
        $partners = ConsignmentPartner::query()
            ->withCount('items')
            ->orderBy('partner_name')
            ->get();

        $items = ConsignmentItem::query()
            ->with(['partner', 'variant.product'])
            ->latest()
            ->paginate(30);

        return view('admin.consignment.index', compact('partners', 'items'));
    }

    // ─── Partners ─────────────────────────────────────────────────────────────

    public function createPartner(): View
    {
        return view('admin.consignment.partner-form', ['partner' => new ConsignmentPartner]);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_name'    => ['required', 'string', 'max:150'],
            'contact_details' => ['nullable', 'string', 'max:500'],
        ]);

        $partner = ConsignmentPartner::query()->create($data);
        ActivityLogger::log('consignment.partner.create', $partner);

        return redirect()->route('admin.consignment.index')->with('status', 'Partner added.');
    }

    public function editPartner(ConsignmentPartner $partner): View
    {
        return view('admin.consignment.partner-form', compact('partner'));
    }

    public function updatePartner(Request $request, ConsignmentPartner $partner): RedirectResponse
    {
        $data = $request->validate([
            'partner_name'    => ['required', 'string', 'max:150'],
            'contact_details' => ['nullable', 'string', 'max:500'],
        ]);

        $partner->update($data);
        ActivityLogger::log('consignment.partner.update', $partner);

        return redirect()->route('admin.consignment.index')->with('status', 'Partner updated.');
    }

    // ─── Items ────────────────────────────────────────────────────────────────

    public function createItem(): View
    {
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();
        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get();

        return view('admin.consignment.item-form', compact('partners', 'variants'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id'        => ['required', 'exists:consignment_partners,id'],
            'variant_id'        => ['required', 'exists:product_variants,id'],
            'units_delivered'   => ['required', 'integer', 'min:1'],
            'agreed_base_price' => ['required', 'numeric', 'min:0'],
            'shop_markup'       => ['required', 'numeric', 'min:0'],
            'received_at'       => ['required', 'date'],
        ]);

        $item = ConsignmentItem::query()->create($data);
        ActivityLogger::log('consignment.item.create', $item);

        return redirect()->route('admin.consignment.index')->with('status', 'Consignment item recorded.');
    }

    // ─── Payouts ──────────────────────────────────────────────────────────────

    public function payouts(): View
    {
        $partners = ConsignmentPartner::query()->orderBy('partner_name')->get();

        $payments = ConsignmentPayment::query()
            ->with('partner')
            ->latest()
            ->paginate(20);

        // Unsettled consignment sales grouped by partner
        $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettled  = SalesItem::query()
            ->with(['variant.product', 'variant.consignmentItems.partner', 'transaction'])
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get()
            ->groupBy(fn (SalesItem $item) => optional($item->variant->consignmentItems->first()?->partner)->id);

        return view('admin.consignment.payouts', compact('partners', 'payments', 'unsettled'));
    }

    public function generatePayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id'   => ['required', 'exists:consignment_partners,id'],
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        return DB::transaction(function () use ($data) {
            $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');

            $salesItems = SalesItem::query()
                ->with(['variant.consignmentItems' => fn ($q) => $q->where('partner_id', $data['partner_id'])])
                ->whereNotIn('id', $settledIds)
                ->whereHas('variant.consignmentItems', fn ($q) => $q->where('partner_id', $data['partner_id']))
                ->get();

            if ($salesItems->isEmpty()) {
                return back()->withErrors(['partner_id' => 'No unsettled items for this partner in the selected period.']);
            }

            $total = $salesItems->sum(function (SalesItem $item) {
                $ci = $item->variant->consignmentItems->first();

                return $ci ? $item->quantity * $ci->agreed_base_price : 0;
            });

            $payment = ConsignmentPayment::query()->create([
                'partner_id'     => $data['partner_id'],
                'user_id'        => auth()->id(),
                'period_start'   => $data['period_start'],
                'period_end'     => $data['period_end'],
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
                    'partner_amount'         => $item->quantity * $ci->agreed_base_price,
                ]);
            }

            ActivityLogger::log('consignment.payout.generate', $payment, null, [
                'total_amount' => $total,
                'item_count'   => $salesItems->count(),
            ]);

            return redirect()->route('admin.consignment.payouts')->with('status', 'Payout generated: ₱'.number_format($total, 2));
        });
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
