<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        // Quick stats for the report hub
        $todayRevenue = SalesTransaction::query()
            ->whereDate('transaction_date', today())
            ->with('items')
            ->get()
            ->sum(fn ($t) => $t->grandTotal());

        $monthRevenue = SalesTransaction::query()
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->with('items')
            ->get()
            ->sum(fn ($t) => $t->grandTotal());

        $pendingPayouts = ConsignmentPayment::where('payment_status', 'pending')->sum('total_amount');

        return view('admin.reports.index', compact('todayRevenue', 'monthRevenue', 'pendingPayouts'));
    }

    // ─── Sales Report ─────────────────────────────────────────────────────────

    public function sales(Request $request): View
    {
        $range = $request->get('range', 'month');
        $from  = $this->rangeStart($range, $request->get('from'));
        $to    = $this->rangeEnd($range, $request->get('to'));

        $transactions = SalesTransaction::query()
            ->with(['items.variant.product', 'customer', 'payments'])
            ->whereBetween('transaction_date', [$from, $to])
            ->latest('transaction_date')
            ->get();

        $totalRevenue      = $transactions->sum(fn ($t) => $t->grandTotal());
        $totalTransactions = $transactions->count();
        $walkInRevenue     = $transactions->where('channel', 'walk_in')->sum(fn ($t) => $t->grandTotal());
        $onlineRevenue     = $transactions->where('channel', 'online')->sum(fn ($t) => $t->grandTotal());
        $walkInCount       = $transactions->where('channel', 'walk_in')->count();
        $onlineCount       = $transactions->where('channel', 'online')->count();
        $avgOrderValue     = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // Daily revenue series for chart (keyed by date string)
        $dailySeries = $transactions
            ->groupBy(fn ($t) => $t->transaction_date->format('Y-m-d'))
            ->map(fn ($group) => round($group->sum(fn ($t) => $t->grandTotal()), 2))
            ->sortKeys();

        // Fill gaps between from..to with 0
        $startDate  = Carbon::parse($from)->startOfDay();
        $endDate    = Carbon::parse($to)->endOfDay();
        $chartLabels = [];
        $chartData   = [];
        $cursor      = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $key           = $cursor->format('Y-m-d');
            $chartLabels[] = $cursor->format('M j');
            $chartData[]   = $dailySeries[$key] ?? 0;
            $cursor->addDay();
        }

        return view('admin.reports.sales', compact(
            'transactions', 'totalRevenue', 'totalTransactions',
            'walkInRevenue', 'onlineRevenue', 'walkInCount', 'onlineCount', 'avgOrderValue',
            'chartLabels', 'chartData',
            'from', 'to', 'range'
        ));
    }

    // ─── Best Sellers ─────────────────────────────────────────────────────────

    public function bestSellers(Request $request): View
    {
        $range = $request->get('range', 'month');
        $from  = $this->rangeStart($range, $request->get('from'));
        $to    = $this->rangeEnd($range, $request->get('to'));

        $items = SalesItem::query()
            ->with('variant.product')
            ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$from, $to]))
            ->selectRaw('variant_id, SUM(quantity) as total_sold, SUM(quantity * price_at_sale) as total_revenue')
            ->groupBy('variant_id')
            ->orderByDesc('total_sold')
            ->limit(15)
            ->get();

        $grandTotalSold    = $items->sum('total_sold');
        $grandTotalRevenue = $items->sum('total_revenue');

        // Chart data
        $chartLabels = $items->map(fn ($i) => $i->variant->displayName())->values()->all();
        $chartData   = $items->map(fn ($i) => (int) $i->total_sold)->values()->all();

        return view('admin.reports.best-sellers', compact(
            'items', 'grandTotalSold', 'grandTotalRevenue',
            'chartLabels', 'chartData',
            'from', 'to', 'range'
        ));
    }

    // ─── Stock Movement ───────────────────────────────────────────────────────

    public function stockMovement(Request $request): View
    {
        $range = $request->get('range', 'week');
        $from  = $this->rangeStart($range, $request->get('from'));
        $to    = $this->rangeEnd($range, $request->get('to'));

        // Manual adjustments (corrections, restock, damage, consignment_return)
        $adjustments = StockAdjustment::query()
            ->with(['variant.product', 'user', 'supplier'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        // Sales deductions (every confirmed sale deducts stock)
        $salesDeductions = SalesItem::query()
            ->with(['variant.product', 'transaction.user'])
            ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$from, $to]))
            ->get();

        // Combine into a unified timeline
        $movements = collect();

        foreach ($adjustments as $adj) {
            $movements->push([
                'date'     => $adj->created_at,
                'product'  => $adj->variant->displayName(),
                'type'     => ucfirst(str_replace('_', ' ', $adj->adjustment_type)),
                'change'   => $adj->quantity_changed,
                'reason'   => $adj->reason,
                'by'       => $adj->user->username,
                'source'   => 'adjustment',
            ]);
        }

        foreach ($salesDeductions as $item) {
            $movements->push([
                'date'     => $item->transaction->transaction_date,
                'product'  => $item->variant->displayName(),
                'type'     => 'Sale ('.(ucfirst(str_replace('_', ' ', $item->transaction->channel))).')',
                'change'   => -$item->quantity,
                'reason'   => 'Transaction #'.$item->transaction_id,
                'by'       => $item->transaction->user->username ?? '—',
                'source'   => 'sale',
            ]);
        }

        $movements = $movements->sortByDesc('date')->values();

        // Summary stats
        $totalRestocked  = $adjustments->where('adjustment_type', 'restock')->sum('quantity_changed');
        $totalDamage     = abs($adjustments->whereIn('adjustment_type', ['damage'])->sum('quantity_changed'));
        $totalSoldUnits  = $salesDeductions->sum('quantity');

        return view('admin.reports.stock-movement', compact(
            'movements', 'adjustments', 'salesDeductions',
            'totalRestocked', 'totalDamage', 'totalSoldUnits',
            'from', 'to', 'range'
        ));
    }

    // ─── Consignment Payables ─────────────────────────────────────────────────

    public function consignment(Request $request): View
    {
        $payments = ConsignmentPayment::query()
            ->with(['partner', 'items.consignmentItem.variant.product'])
            ->withCount('items')
            ->latest()
            ->get();

        $totalPaid    = $payments->where('payment_status', 'paid')->sum('total_amount');
        $totalPending = $payments->where('payment_status', 'pending')->sum('total_amount');
        $paidCount    = $payments->where('payment_status', 'paid')->count();
        $pendingCount = $payments->where('payment_status', 'pending')->count();

        // Unsettled sales (not in any payout yet)
        $settledIds  = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettled   = SalesItem::query()
            ->with(['variant.consignmentItems.partner', 'variant.product', 'transaction'])
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get();

        $unsettledAmount = $unsettled->sum(
            fn ($si) => (float) ($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity
        );

        return view('admin.reports.consignment', compact(
            'payments', 'totalPaid', 'totalPending', 'paidCount', 'pendingCount',
            'unsettled', 'unsettledAmount'
        ));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function rangeStart(string $range, ?string $custom): string
    {
        return match ($range) {
            'today'  => now()->startOfDay()->toDateTimeString(),
            'week'   => now()->startOfWeek()->toDateTimeString(),
            'month'  => now()->startOfMonth()->toDateTimeString(),
            'custom' => $custom ? $custom.' 00:00:00' : now()->startOfMonth()->toDateTimeString(),
            default  => now()->startOfDay()->toDateTimeString(),
        };
    }

    private function rangeEnd(string $range, ?string $custom): string
    {
        return match ($range) {
            'custom' => $custom ? $custom.' 23:59:59' : now()->endOfDay()->toDateTimeString(),
            default  => now()->endOfDay()->toDateTimeString(),
        };
    }
}
