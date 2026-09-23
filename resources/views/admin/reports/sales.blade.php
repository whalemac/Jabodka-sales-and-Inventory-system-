<x-layouts.app title="Sales Report">

@push('styles')
<style>
@media print {
    .print\:hidden { display: none !important; }
    body { background: white; }
    .bg-sand { background: white; }
}
</style>
@endpush

<div class="flex items-center justify-between mb-5 print:mb-3">
    <div>
        <h1 class="text-2xl font-bold text-ink">Sales Report</h1>
        <p class="text-sm text-gray-500 mt-0.5 print:text-xs">
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

@include('admin.reports._range_filter', ['action' => route('admin.reports.sales')])

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-kpi-card label="Total Revenue"   value="₱{{ number_format($totalRevenue, 2) }}"   color="navy" />
    <x-kpi-card label="Transactions"    value="{{ $totalTransactions }}"                  color="white" />
    <x-kpi-card label="Avg Order Value" value="₱{{ number_format($avgOrderValue, 2) }}"  color="white" />
    <x-kpi-card label="Walk-In / Online"
                value="{{ $walkInCount }} / {{ $onlineCount }}"
                sub="₱{{ number_format($walkInRevenue, 2) }} / ₱{{ number_format($onlineRevenue, 2) }}"
                color="white" />
</div>

{{-- Revenue chart --}}
@if(count($chartLabels) > 1)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 print:hidden">
    <h2 class="font-semibold text-ink text-sm mb-4">Daily Revenue</h2>
    <div style="height: 220px;">
        <canvas id="salesChart"></canvas>
    </div>
</div>
@endif

{{-- Channel split --}}
@if($totalRevenue > 0)
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-3 h-3 rounded-full bg-navy shrink-0"></span>
            <p class="text-sm font-semibold text-ink">Walk-In Sales</p>
        </div>
        <p class="text-2xl font-bold text-ink">₱{{ number_format($walkInRevenue, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">
            {{ $walkInCount }} {{ Str::plural('transaction', $walkInCount) }}
            · {{ $totalRevenue > 0 ? number_format($walkInRevenue / $totalRevenue * 100, 1) : 0 }}% of total
        </p>
        <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-navy rounded-full" style="width: {{ $totalRevenue > 0 ? ($walkInRevenue / $totalRevenue * 100) : 0 }}%"></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-3 h-3 rounded-full bg-accent shrink-0"></span>
            <p class="text-sm font-semibold text-ink">Online Sales</p>
        </div>
        <p class="text-2xl font-bold text-ink">₱{{ number_format($onlineRevenue, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">
            {{ $onlineCount }} {{ Str::plural('transaction', $onlineCount) }}
            · {{ $totalRevenue > 0 ? number_format($onlineRevenue / $totalRevenue * 100, 1) : 0 }}% of total
        </p>
        <div class="mt-3 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-accent rounded-full" style="width: {{ $totalRevenue > 0 ? ($onlineRevenue / $totalRevenue * 100) : 0 }}%"></div>
        </div>
    </div>
</div>
@endif

{{-- Transactions table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-ink text-sm">Transaction Detail</h2>
        <span class="text-xs text-gray-400">{{ $totalTransactions }} transactions</span>
    </div>
    @if($transactions->isEmpty())
        <div class="px-6 py-10 text-center text-sm text-gray-400">No transactions in this period.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-sand border-b border-gray-100 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Date & Time</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Channel</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Customer</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Items</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Total</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Paid</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($transactions as $t)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                        {{ $t->transaction_date->format('M j, Y') }}<br>
                        <span class="text-gray-400">{{ $t->transaction_date->format('g:i A') }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $t->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }}">
                            {{ $t->channel === 'walk_in' ? 'Walk-In' : 'Online' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $t->customer?->name ?? 'Anonymous' }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $t->items->count() }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-ink">₱{{ number_format($t->grandTotal(), 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $t->isPaid() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $t->isPaid() ? 'Paid' : 'Pending' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-sand border-t border-gray-200">
                    <td colspan="4" class="px-5 py-3 font-semibold text-ink text-right">Total</td>
                    <td class="px-5 py-3 text-right font-bold text-lg text-navy">₱{{ number_format($totalRevenue, 2) }}</td>
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
@if(count($chartLabels) > 1)
const ctx = document.getElementById('salesChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Revenue (₱)',
                data: {!! json_encode($chartData) !!},
                backgroundColor: 'rgba(35, 57, 93, 0.8)',
                borderColor: 'rgba(35, 57, 93, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => '₱' + v.toLocaleString()
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}
@endif
</script>
@endpush

</x-layouts.app>
