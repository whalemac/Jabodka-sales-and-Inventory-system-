<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\StockAdjustment;
use App\Models\StockReceiptItem;
use App\Support\ReportXlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $transactions = $this->salesTransactions($from, $to);

        $totalRevenue = $transactions->sum(fn ($t) => $t->grandTotal());
        $totalTransactions = $transactions->count();
        $walkInRevenue = $transactions->where('channel', 'walk_in')->sum(fn ($t) => $t->grandTotal());
        $onlineRevenue = $transactions->where('channel', 'online')->sum(fn ($t) => $t->grandTotal());
        $walkInCount = $transactions->where('channel', 'walk_in')->count();
        $onlineCount = $transactions->where('channel', 'online')->count();
        $avgOrderValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // Daily revenue series for chart (keyed by date string)
        $dailySeries = $transactions
            ->groupBy(fn ($t) => $t->transaction_date->format('Y-m-d'))
            ->map(fn ($group) => round($group->sum(fn ($t) => $t->grandTotal()), 2))
            ->sortKeys();

        // Fill gaps between from..to with 0
        $startDate = Carbon::parse($from)->startOfDay();
        $endDate = Carbon::parse($to)->endOfDay();
        $chartLabels = [];
        $chartData = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $key = $cursor->format('Y-m-d');
            $chartLabels[] = $cursor->format('M j');
            $chartData[] = $dailySeries[$key] ?? 0;
            $cursor->addDay();
        }

        $rows = $this->paginateRows($request, $transactions);

        return view('admin.reports.sales', compact(
            'transactions', 'totalRevenue', 'totalTransactions',
            'walkInRevenue', 'onlineRevenue', 'walkInCount', 'onlineCount', 'avgOrderValue',
            'chartLabels', 'chartData',
            'from', 'to', 'range',
            'rows'
        ));
    }

    public function exportSales(Request $request): StreamedResponse
    {
        $range = $request->get('range', 'month');
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $transactions = $this->salesTransactions($from, $to);

        $rows = $transactions->map(fn ($t) => [
            $t->transaction_date->format('M j, Y g:i A'),
            $t->channel === 'walk_in' ? 'Walk-In' : 'Online',
            $t->customer?->name ?? 'Anonymous',
            (int) $t->items->count(),
            round($t->grandTotal(), 2),
            $t->isPaid() ? 'Paid' : 'Pending',
        ])->values();

        $rows->push(['', '', 'Total', null, round($transactions->sum(fn ($t) => $t->grandTotal()), 2), '']);

        return ReportXlsx::stream(
            'sales-report_'.$this->filePeriod($from, $to).'.xlsx',
            [
                'title'     => 'Sales Report',
                'period'    => 'Period: '.$this->periodLabel($from, $to),
                'headers'   => ['Date & Time', 'Channel', 'Customer', 'Items', 'Total', 'Paid'],
                'rows'      => $rows->all(),
                'formats'   => [3 => 'int', 4 => 'money'],
                'boldRows'  => [$rows->count() - 1],
            ]
        );
    }

    private function salesTransactions(string $from, string $to)
    {
        return SalesTransaction::query()
            ->with(['items.variant.product', 'customer', 'payments'])
            ->whereBetween('transaction_date', [$from, $to])
            ->latest('transaction_date')
            ->get();
    }

    // ─── Best Sellers ─────────────────────────────────────────────────────────

    public function bestSellers(Request $request): View
    {
        $range = $request->get('range', 'month');
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $items = $this->bestSellerItems($from, $to);

        $rows = $this->paginateRows($request, $items);

        $grandTotalSold = $items->sum('total_sold');
        $grandTotalRevenue = $items->sum('total_revenue');

        // Chart data (top 15 only, keeps the chart readable)
        $chartItems = $items->take(15);
        $chartLabels = $chartItems->map(fn ($i) => $i->variant->displayName())->values()->all();
        $chartData = $chartItems->map(fn ($i) => (int) $i->total_sold)->values()->all();

        return view('admin.reports.best-sellers', compact(
            'items', 'rows', 'grandTotalSold', 'grandTotalRevenue',
            'chartLabels', 'chartData',
            'from', 'to', 'range'
        ));
    }

    public function exportBestSellers(Request $request): StreamedResponse
    {
        $range = $request->get('range', 'month');
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $items = $this->bestSellerItems($from, $to);

        $grandTotalSold = (int) $items->sum('total_sold');
        $grandTotalRevenue = round($items->sum('total_revenue'), 2);

        $rows = $items->map(function ($item) use ($grandTotalSold) {
            $sold = (int) $item->total_sold;

            return [
                $item->variant->product->name,
                $item->variant->label() ?: 'Standard',
                ucfirst($item->variant->product->source_type),
                $sold,
                round((float) $item->total_revenue, 2),
                $grandTotalSold > 0 ? round($sold / $grandTotalSold * 100, 1) : 0,
            ];
        })->values();

        $rows->push(['Total', '', '', $grandTotalSold, $grandTotalRevenue, 100]);

        return ReportXlsx::stream(
            'best-sellers_'.$this->filePeriod($from, $to).'.xlsx',
            [
                'title'     => 'Best Sellers',
                'period'    => 'Period: '.$this->periodLabel($from, $to).' · Top '.$items->count().' variants',
                'headers'   => ['Product', 'Variant', 'Source', 'Units Sold', 'Revenue', '% of Units'],
                'rows'      => $rows->all(),
                'formats'   => [3 => 'int', 4 => 'money'],
                'boldRows'  => [$rows->count() - 1],
            ]
        );
    }

    private function bestSellerItems(string $from, string $to)
    {
        return SalesItem::query()
            ->with('variant.product')
            ->whereHas('transaction', fn ($q) => $q->whereBetween('transaction_date', [$from, $to]))
            ->selectRaw('variant_id, SUM(quantity) as total_sold, SUM(quantity * price_at_sale) as total_revenue')
            ->groupBy('variant_id')
            ->orderByDesc('total_sold')
            ->get();
    }

    // ─── Stock Movement ───────────────────────────────────────────────────────

    public function stockMovement(Request $request): View
    {
        $range = $request->get('range', 'week');
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $data = $this->stockMovementData($from, $to);
        $movements = $data['movements'];

        $rows = $this->paginateRows($request, $movements);

        return view('admin.reports.stock-movement', array_merge($data, compact(
            'from', 'to', 'range',
            'rows'
        )));
    }

    public function exportStockMovement(Request $request): StreamedResponse
    {
        $range = $request->get('range', 'week');
        $from = $this->rangeStart($range, $request->get('from'));
        $to = $this->rangeEnd($range, $request->get('to'));

        $data = $this->stockMovementData($from, $to);

        $rows = $data['movements']->map(fn ($m) => [
            Carbon::instance($m['date'])->format('M j, Y g:i A'),
            $m['product'],
            $m['type'],
            (int) $m['change'],
            $m['reason'],
            $m['by'],
        ])->values();

        return ReportXlsx::stream(
            'stock-movement_'.$this->filePeriod($from, $to).'.xlsx',
            [
                'title'     => 'Stock Movement',
                'period'    => 'Period: '.$this->periodLabel($from, $to)
                    .' · Restocked: '.number_format($data['totalRestocked'])
                    .' · Sold: '.number_format($data['totalSoldUnits'])
                    .' · Damage/Loss: '.number_format($data['totalDamage']),
                'headers'   => ['Date & Time', 'Product / Variant', 'Movement Type', 'Change', 'Reference / Reason', 'By'],
                'rows'      => $rows->all(),
                'formats'   => [3 => 'int'],
            ]
        );
    }

    private function stockMovementData(string $from, string $to): array
    {
        // Manual adjustments (corrections, restock, damage, consignment_return)
        $adjustments = StockAdjustment::query()
            ->with(['variant.product', 'user', 'supplier'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $receiptItems = StockReceiptItem::query()
            ->with(['variant.product', 'receipt.user'])
            ->whereHas('receipt', fn ($q) => $q->whereBetween('received_date', [$from, $to]))
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
                'date' => $adj->created_at,
                'product' => $adj->variant->displayName(),
                'type' => ucfirst(str_replace('_', ' ', $adj->adjustment_type)),
                'change' => $adj->quantity_changed,
                'reason' => $adj->reason,
                'by' => $adj->user->username,
                'source' => 'adjustment',
            ]);
        }

        foreach ($receiptItems as $line) {
            $movements->push([
                'date' => $line->receipt->received_date,
                'product' => $line->variant->displayName(),
                'type' => 'Restock (Invoice)',
                'change' => $line->quantity_received,
                'reason' => 'Invoice '.$line->receipt->invoice_number,
                'by' => $line->receipt->user->username ?? '—',
                'source' => 'receipt',
            ]);
        }

        foreach ($salesDeductions as $item) {
            $movements->push([
                'date' => $item->transaction->transaction_date,
                'product' => $item->variant->displayName(),
                'type' => 'Sale ('.(ucfirst(str_replace('_', ' ', $item->transaction->channel))).')',
                'change' => -$item->quantity,
                'reason' => 'Transaction #'.$item->transaction_id,
                'by' => $item->transaction->user->username ?? '—',
                'source' => 'sale',
            ]);
        }

        $movements = $movements->sortByDesc('date')->values();

        // Summary stats
        $totalRestocked = $adjustments->where('adjustment_type', 'restock')->sum('quantity_changed')
            + $receiptItems->sum('quantity_received');
        $totalDamage = abs($adjustments->whereIn('adjustment_type', ['damage'])->sum('quantity_changed'));
        $totalSoldUnits = $salesDeductions->sum('quantity');

        return [
            'movements'      => $movements,
            'adjustments'    => $adjustments,
            'salesDeductions' => $salesDeductions,
            'totalRestocked' => $totalRestocked,
            'totalDamage'    => $totalDamage,
            'totalSoldUnits' => $totalSoldUnits,
        ];
    }

    // ─── Consignment Payables ─────────────────────────────────────────────────

    public function consignment(Request $request): View
    {
        $data = $this->consignmentData();

        $unsettledRows = $this->paginateRows($request, $data['unsettled'], 'upage');
        $paymentRows = $this->paginateRows($request, $data['payments']);

        return view('admin.reports.consignment', array_merge($data, compact(
            'unsettledRows', 'paymentRows'
        )));
    }

    public function exportConsignment(Request $request): StreamedResponse
    {
        $data = $this->consignmentData();

        $unsettledRows = $data['unsettled']->map(function ($si) {
            $ci = $si->variant->consignmentItems->first();

            return [
                $ci?->partner->partner_name ?? '—',
                $si->variant->displayName(),
                (int) $si->quantity,
                round((float) ($ci?->agreed_base_price ?? 0), 2),
                round((float) ($ci ? $si->quantity * $ci->agreed_base_price : 0), 2),
                $si->transaction->transaction_date->format('M j, Y'),
            ];
        })->values();

        $unsettledRows->push(['Total Unsettled', '', null, null, round((float) $data['unsettledAmount'], 2), '']);

        $paymentRows = $data['payments']->map(fn ($payment) => [
            $payment->partner->partner_name,
            $payment->period_start->format('M j').' – '.$payment->period_end->format('M j, Y'),
            (int) $payment->items_count,
            round((float) $payment->total_amount, 2),
            ucfirst($payment->payment_status),
            $payment->payment_date?->format('M j, Y') ?? '—',
            $payment->reference_number ?? '',
        ])->values();

        $paymentRows->push(['Totals', '', null, round((float) ($data['totalPaid'] + $data['totalPending']), 2), '', '', '']);

        return ReportXlsx::stream(
            'consignment-payables_'.now()->format('Y-m-d').'.xlsx',
            [
                'title'     => 'Consignment Payables',
                'period'    => 'Generated on: '.now()->format('M j, Y g:i A'),
                'headers'   => ['Partner', 'Product', 'Qty Sold', 'Base Price', 'Amount Owed', 'Sale Date'],
                'rows'      => $unsettledRows->all(),
                'formats'   => [3 => 'money', 4 => 'money'],
                'boldRows'  => [$unsettledRows->count() - 1],
                'sections'  => [
                    [
                        'title'   => 'Payout History',
                        'headers' => ['Partner', 'Period', 'Items', 'Amount', 'Status', 'Paid Date', 'Reference'],
                        'rows'    => $paymentRows->all(),
                        'formats' => [3 => 'money'],
                        'boldRows' => [$paymentRows->count() - 1],
                    ],
                ],
            ]
        );
    }

    private function consignmentData(): array
    {
        $payments = ConsignmentPayment::query()
            ->with(['partner', 'items.consignmentItem.variant.product'])
            ->withCount('items')
            ->latest()
            ->get();

        $totalPaid = $payments->where('payment_status', 'paid')->sum('total_amount');
        $totalPending = $payments->where('payment_status', 'pending')->sum('total_amount');
        $paidCount = $payments->where('payment_status', 'paid')->count();
        $pendingCount = $payments->where('payment_status', 'pending')->count();

        // Unsettled sales (not in any payout yet)
        $settledIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $unsettled = SalesItem::query()
            ->with(['variant.consignmentItems.partner', 'variant.product', 'transaction'])
            ->whereNotIn('id', $settledIds)
            ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->get();

        $unsettledAmount = $unsettled->sum(
            fn ($si) => (float) ($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity
        );

        return [
            'payments'         => $payments,
            'totalPaid'        => $totalPaid,
            'totalPending'     => $totalPending,
            'paidCount'        => $paidCount,
            'pendingCount'     => $pendingCount,
            'unsettled'        => $unsettled,
            'unsettledAmount'  => $unsettledAmount,
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function paginateRows(Request $request, $collection, string $pageName = 'page', int $perPage = 25): LengthAwarePaginator
    {
        $page = max(1, (int) $request->get($pageName, 1));

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path'     => $request->url(),
                'query'    => $request->query(),
                'pageName' => $pageName,
            ]
        );
    }

    private function periodLabel(string $from, string $to): string
    {
        return Carbon::parse($from)->format('M j, Y').' – '.Carbon::parse($to)->format('M j, Y');
    }

    private function filePeriod(string $from, string $to): string
    {
        return Carbon::parse($from)->format('Y-m-d').'_'.Carbon::parse($to)->format('Y-m-d');
    }

    private function rangeStart(string $range, ?string $custom): string
    {
        return match ($range) {
            'today' => now()->startOfDay()->toDateTimeString(),
            'week' => now()->startOfWeek()->toDateTimeString(),
            'month' => now()->startOfMonth()->toDateTimeString(),
            'custom' => $custom ? $custom.' 00:00:00' : now()->startOfMonth()->toDateTimeString(),
            default => now()->startOfDay()->toDateTimeString(),
        };
    }

    private function rangeEnd(string $range, ?string $custom): string
    {
        return match ($range) {
            'custom' => $custom ? $custom.' 23:59:59' : now()->endOfDay()->toDateTimeString(),
            default => now()->endOfDay()->toDateTimeString(),
        };
    }
}
