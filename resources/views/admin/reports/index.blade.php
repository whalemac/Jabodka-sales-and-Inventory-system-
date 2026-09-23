<x-layouts.app title="Reports">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">Reports</h1>
        <p class="text-sm text-gray-500 mt-0.5">Sales analytics, inventory, and consignment summaries</p>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <x-kpi-card
            label="Today's Revenue"
            value="₱{{ number_format($todayRevenue, 2) }}"
            color="navy"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\"/></svg>'"
        />
        <x-kpi-card
            label="{{ now()->format('F') }} Revenue"
            value="₱{{ number_format($monthRevenue, 2) }}"
            color="white"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg>'"
        />
        <x-kpi-card
            label="Consignment Due"
            value="₱{{ number_format($pendingPayouts, 2) }}"
            color="{{ $pendingPayouts > 0 ? 'accent' : 'white' }}"
            href="{{ route('admin.reports.consignment') }}"
            :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z\"/></svg>'"
        />
    </div>

    {{-- Report links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('admin.reports.sales') }}"
           class="flex flex-col gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink">Sales Report</p>
                <p class="text-xs text-gray-500 mt-0.5">Revenue by period, channel breakdown, daily chart</p>
            </div>
        </a>

        <a href="{{ route('admin.reports.best-sellers') }}"
           class="flex flex-col gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink">Best Sellers</p>
                <p class="text-xs text-gray-500 mt-0.5">Top products by units sold and revenue</p>
            </div>
        </a>

        <a href="{{ route('admin.reports.stock-movement') }}"
           class="flex flex-col gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink">Stock Movement</p>
                <p class="text-xs text-gray-500 mt-0.5">Sales deductions, restocks, adjustments</p>
            </div>
        </a>

        <a href="{{ route('admin.reports.consignment') }}"
           class="flex flex-col gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:border-navy hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-ink">Consignment Payables</p>
                <p class="text-xs text-gray-500 mt-0.5">Paid vs pending partner payouts</p>
            </div>
        </a>
    </div>

</x-layouts.app>
