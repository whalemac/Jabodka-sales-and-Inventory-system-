<x-layouts.app title="Dashboard">

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ now()->format('l, F j, Y') }} &mdash; Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, <strong>{{ auth()->user()->username }}</strong>
        </p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <x-kpi-card
            label="Today's Sales"
            value="₱{{ number_format($todaySales, 2) }}"
            sub="All channels"
            color="navy"
            href="{{ route('admin.walk-in.index') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\"/></svg>'"
        />

        <x-kpi-card
            label="Low Stock"
            value="{{ $lowStock }}"
            sub="{{ $lowStock === 1 ? 'variant needs restocking' : 'variants need restocking' }}"
            color="{{ $lowStock > 0 ? 'red' : 'green' }}"
            href="{{ route('admin.stock.index') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h12a2 2 0 012 2v2\"/></svg>'"
        />

        <x-kpi-card
            label="Pending Online"
            value="{{ $pendingOnline }}"
            sub="{{ $pendingOnline === 1 ? 'order awaiting payment' : 'orders awaiting payment' }}"
            color="{{ $pendingOnline > 0 ? 'accent' : 'white' }}"
            href="{{ route('admin.online.index') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z\"/></svg>'"
        />

        <x-kpi-card
            label="Consignment Due"
            value="₱{{ number_format($payablesDue, 2) }}"
            sub="{{ $unsettledCount }} unsettled {{ Str::plural('item', $unsettledCount) }}"
            color="{{ $payablesDue > 0 ? 'accent' : 'white' }}"
            href="{{ route('admin.consignment.payouts') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z\"/></svg>'"
        />
    </div>

    {{-- Quick Actions --}}
    <div class="mb-8">
        <h2 class="text-base font-semibold text-ink mb-3">Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

            <a href="{{ route('admin.walk-in.create') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">New Sale</span>
            </a>

            <a href="{{ route('admin.online.create') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">Online Order</span>
            </a>

            <a href="{{ route('admin.stock.import-form') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">Add Stock</span>
            </a>

            <a href="{{ route('admin.products.create') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">New Product</span>
            </a>

            <a href="{{ route('admin.stock.index') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h12a2 2 0 012 2v2"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">Stock Levels</span>
            </a>

            <a href="{{ route('admin.reports.index') }}"
               class="flex flex-col items-center gap-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy hover:shadow-md transition text-center group">
                <div class="w-11 h-11 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                    <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-ink leading-tight">Reports</span>
            </a>
        </div>
    </div>

    {{-- Module overview grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Recent Walk-In Sales --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Recent Walk-In Sales</h2>
                <a href="{{ route('admin.walk-in.index') }}" class="text-xs text-navy font-medium hover:underline">View all</a>
            </div>
            @php
                $recentSales = \App\Models\SalesTransaction::query()
                    ->with(['items', 'user'])
                    ->where('channel', 'walk_in')
                    ->latest('transaction_date')
                    ->limit(5)
                    ->get();
            @endphp
            @if($recentSales->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">No sales recorded yet today.</div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($recentSales as $sale)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink">
                                {{ $sale->items->count() }} {{ Str::plural('item', $sale->items->count()) }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $sale->transaction_date->format('M j · g:i A') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-ink">₱{{ number_format($sale->grandTotal(), 2) }}</p>
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $sale->isPaid() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $sale->isPaid() ? 'Paid' : 'Unpaid' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Low Stock Alerts --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Low Stock Alerts</h2>
                <a href="{{ route('admin.stock.index') }}" class="text-xs text-navy font-medium hover:underline">View all</a>
            </div>
            @php
                $lowStockItems = \App\Models\ProductVariant::query()
                    ->with('product')
                    ->lowStock()
                    ->orderBy('stock_count')
                    ->limit(6)
                    ->get();
            @endphp
            @if($lowStockItems->isEmpty())
                <div class="px-5 py-8 text-center">
                    <svg class="w-8 h-8 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400">All stock levels are healthy.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($lowStockItems as $variant)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ $variant->product->name }}</p>
                            <p class="text-xs text-gray-400">{{ $variant->label() }}</p>
                        </div>
                        <div class="text-right ml-4 shrink-0">
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $variant->stock_count === 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $variant->stock_count }} left
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pending Online Orders --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Pending Online Orders</h2>
                <a href="{{ route('admin.online.index') }}" class="text-xs text-navy font-medium hover:underline">View all</a>
            </div>
            @php
                $pendingOrders = \App\Models\SalesTransaction::query()
                    ->with(['items', 'customer'])
                    ->where('channel', 'online')
                    ->whereIn('shipment_status', ['pending_payment', 'ready_to_ship'])
                    ->latest('transaction_date')
                    ->limit(5)
                    ->get();
            @endphp
            @if($pendingOrders->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">No pending online orders.</div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($pendingOrders as $order)
                    <a href="{{ route('admin.online.show', $order) }}" class="flex items-center justify-between px-5 py-3 hover:bg-sand transition">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">
                                {{ $order->customer?->name ?? 'Walk-in' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $order->transaction_date->format('M j · g:i A') }}</p>
                        </div>
                        <div class="text-right ml-4 shrink-0">
                            @php
                                $statusColors = [
                                    'pending_payment' => 'bg-red-100 text-red-700',
                                    'ready_to_ship'   => 'bg-blue-100 text-blue-700',
                                ];
                                $statusLabels = [
                                    'pending_payment' => 'Unpaid',
                                    'ready_to_ship'   => 'Ready',
                                ];
                            @endphp
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusColors[$order->shipment_status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $statusLabels[$order->shipment_status] ?? $order->shipment_status }}
                            </span>
                            <p class="text-xs text-gray-500 mt-0.5">₱{{ number_format($order->grandTotal(), 2) }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Consignment Payables --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Consignment Payables</h2>
                <a href="{{ route('admin.consignment.payouts') }}" class="text-xs text-navy font-medium hover:underline">Manage payouts</a>
            </div>
            @php
                $pendingPayouts = \App\Models\ConsignmentPayment::query()
                    ->with('partner')
                    ->where('payment_status', 'pending')
                    ->latest()
                    ->limit(5)
                    ->get();
            @endphp
            @if($pendingPayouts->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-400">No pending consignment payouts.</div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($pendingPayouts as $payout)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ $payout->partner->partner_name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $payout->period_start->format('M j') }} – {{ $payout->period_end->format('M j, Y') }}
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-accent ml-4 shrink-0">₱{{ number_format($payout->total_amount, 2) }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</x-layouts.app>
