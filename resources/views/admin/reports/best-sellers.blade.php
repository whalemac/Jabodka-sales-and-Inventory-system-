<x-layouts.app title="Best Sellers">

<div class="flex items-center justify-between mb-5 print:mb-3">
    <div>
        <h1 class="text-2xl font-bold text-ink">Best Sellers</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ \Carbon\Carbon::parse($from)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}
        </p>
    </div>
    <div class="flex items-center gap-3 print:hidden">
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-navy">← Reports</a>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print / PDF
        </button>
    </div>
</div>

@include('admin.reports._range_filter', ['action' => route('admin.reports.best-sellers')])

{{-- Summary --}}
@if($items->isNotEmpty())
<div class="grid grid-cols-2 gap-4 mb-6">
    <x-kpi-card label="Total Units Sold" value="{{ number_format($grandTotalSold) }}" color="navy" />
    <x-kpi-card label="Total Revenue"    value="₱{{ number_format($grandTotalRevenue, 2) }}" color="white" />
</div>

{{-- Chart --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 print:hidden">
    <h2 class="font-semibold text-ink text-sm mb-4">Units Sold by Variant</h2>
    <div style="height: {{ min(300, count($chartLabels) * 32 + 40) }}px;">
        <canvas id="bestsellersChart"></canvas>
    </div>
</div>
@endif

{{-- Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if($items->isEmpty())
        <div class="px-6 py-10 text-center text-sm text-gray-400">No sales data for this period.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-sand border-b border-gray-100 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600 w-10">#</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Source</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Units Sold</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Revenue</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">% of Units</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($items as $idx => $item)
                @php
                    $pct = $grandTotalSold > 0 ? ($item->total_sold / $grandTotalSold * 100) : 0;
                @endphp
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                            {{ $idx < 3 ? 'bg-navy text-white' : 'bg-gray-100 text-gray-600' }}">
                            {{ $idx + 1 }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 font-medium text-ink">
                        {{ $item->variant->product->name }}
                        <br><span class="text-xs text-gray-400 font-normal">{{ $item->variant->label() ?: 'Standard' }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                            {{ match($item->variant->product->source_type) {
                                'handmade'    => 'bg-blue-100 text-blue-700',
                                'sourced'     => 'bg-purple-100 text-purple-700',
                                'consignment' => 'bg-orange-100 text-orange-700',
                                default       => 'bg-gray-100 text-gray-600'
                            } }}">
                            {{ ucfirst($item->variant->product->source_type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-center font-bold text-ink">{{ number_format($item->total_sold) }}</td>
                    <td class="px-5 py-3.5 text-right font-semibold text-ink">₱{{ number_format($item->total_revenue, 2) }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-navy rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-10 text-right">{{ number_format($pct, 1) }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-sand border-t border-gray-200">
                    <td colspan="3" class="px-5 py-3 font-semibold text-ink text-right">Total</td>
                    <td class="px-5 py-3 text-center font-bold text-navy">{{ number_format($grandTotalSold) }}</td>
                    <td class="px-5 py-3 text-right font-bold text-navy">₱{{ number_format($grandTotalRevenue, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if(count($chartLabels) > 0)
const ctx = document.getElementById('bestsellersChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Units Sold',
                data: {!! json_encode($chartData) !!},
                backgroundColor: [
                    'rgba(35,57,93,0.9)', 'rgba(35,57,93,0.75)', 'rgba(35,57,93,0.6)',
                    ...Array(12).fill('rgba(35,57,93,0.4)')
                ],
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                y: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
}
@endif
</script>
@endpush

</x-layouts.app>
