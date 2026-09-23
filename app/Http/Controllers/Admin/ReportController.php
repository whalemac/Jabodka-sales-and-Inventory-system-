<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request): View
    {
        $range  = $request->get('range', 'today');
        $from   = $this->rangeStart($range, $request->get('from'));
        $to     = $this->rangeEnd($range, $request->get('to'));

        $transactions = SalesTransaction::query()
            ->with(['items.variant.product', 'customer', 'payments'])
            ->whereBetween('transaction_date', [$from, $to])
            ->latest('transaction_date')
            ->get();

        $totalRevenue   = $transactions->sum(fn ($t) => $t->grandTotal());
        $totalTransactions = $transactions->count();

        return view('admin.reports.sales', compact('transactions', 'totalRevenue', 'totalTransactions', 'from', 'to', 'range'));
    }

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
            ->limit(20)
            ->get();

        return view('admin.reports.best-sellers', compact('items', 'from', 'to', 'range'));
    }

    public function stockMovement(Request $request): View
    {
        $range = $request->get('range', 'week');
        $from  = $this->rangeStart($range, $request->get('from'));
        $to    = $this->rangeEnd($range, $request->get('to'));

        $adjustments = StockAdjustment::query()
            ->with(['variant.product', 'user', 'supplier'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.reports.stock-movement', compact('adjustments', 'from', 'to', 'range'));
    }

    public function consignment(Request $request): View
    {
        $payments = \App\Models\ConsignmentPayment::query()
            ->with(['partner', 'items.consignmentItem.variant.product'])
            ->latest()
            ->paginate(20);

        return view('admin.reports.consignment', compact('payments'));
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
