<?php

namespace App\Http\Controllers;

use App\Models\ConsignmentPayment;
use App\Models\ConsignmentPaymentItem;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard.super', [
                'userCount' => User::query()->count(),
                'activeSubs' => Subscription::query()->where('status', 'active')->count(),
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
            ->whereDoesntHave('payments', fn ($q) => $q->where('payment_status', 'confirmed'))
            ->count();

        $settledItemIds = ConsignmentPaymentItem::query()->pluck('sales_item_id');
        $payablesDue = ConsignmentPayment::query()->where('payment_status', 'pending')->sum('total_amount');

        return view('dashboard.admin', [
            'todaySales' => $todaySales,
            'lowStock' => $lowStock,
            'pendingOnline' => $pendingOnline,
            'payablesDue' => $payablesDue,
            'unsettledCount' => SalesItem::query()
                ->whereNotIn('id', $settledItemIds)
                ->whereHas('variant.product', fn ($q) => $q->where('source_type', 'consignment'))
                ->count(),
        ]);
    }
}
