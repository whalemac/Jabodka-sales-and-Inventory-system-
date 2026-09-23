<x-layouts.app title="Reports">
    <h1 class="text-2xl font-bold text-ink mb-6">Reports</h1>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['route' => 'admin.reports.sales',         'label' => 'Sales Report',         'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['route' => 'admin.reports.best-sellers',  'label' => 'Best Sellers',         'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
            ['route' => 'admin.reports.stock-movement','label' => 'Stock Movement',       'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
            ['route' => 'admin.reports.consignment',   'label' => 'Consignment Payables', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ] as $report)
        <a href="{{ route($report['route']) }}"
           class="flex flex-col items-center gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-navy hover:shadow-md transition text-center group">
            <div class="w-12 h-12 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition">
                <svg class="w-6 h-6 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $report['icon'] }}"/>
                </svg>
            </div>
            <span class="text-sm font-semibold text-ink">{{ $report['label'] }}</span>
        </a>
        @endforeach
    </div>
</x-layouts.app>
