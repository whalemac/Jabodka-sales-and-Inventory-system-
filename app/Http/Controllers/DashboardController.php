<?php

namespace App\Http\Controllers;

use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\ProductVariant;
use App\Models\SalesTransaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard.super', [
                'userCount' => \App\Models\User::query()->count(),
                'activeSubs' => \App\Models\Subscription::query()->where('status', 'active')->count(),
            ]);
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.production.index');
        }

        $todaySales = SalesTransaction::query()
            ->whereDate('transaction_date', today())
            ->with('items')
            ->get()
            ->sum(fn (SalesTransaction $sale) => $sale->grandTotal());

        $lowStock = ProductVariant::query()->with('product')->lowStock()->count();

        $pendingOnline = SalesTransaction::query()
            ->where('channel', 'online')
            ->where('shipment_status', 'pending_payment')
            ->count();

        $settledItemIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $payablesDue = ConsignmentPayment::query()->where('payment_status', 'pending')->sum('total_amount');

        return view('dashboard.admin', [
            'todaySales' => $todaySales,
            'lowStock' => $lowStock,
            'pendingOnline' => $pendingOnline,
            'payablesDue' => $payablesDue,
            'unsettledCount' => \App\Models\SalesItem::query()
                ->whereNotIn('id', $settledItemIds)
                ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
                ->count(),
        ]);
    }
}
