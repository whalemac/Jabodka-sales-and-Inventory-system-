<x-layouts.app title="Stock Movement">

<div class="flex items-center justify-between mb-5 print:mb-3">
    <div>
        <h1 class="text-2xl font-bold text-ink">Stock Movement</h1>
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

@include('admin.reports._range_filter', ['action' => route('admin.reports.stock-movement')])

{{-- Summary cards --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <x-kpi-card label="Units Restocked"   value="{{ number_format($totalRestocked) }}"    color="navy" />
    <x-kpi-card label="Units Sold"        value="{{ number_format($totalSoldUnits) }}"    color="white"
        :icon="'<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z\"/></svg>'" />
    <x-kpi-card label="Damage / Loss"     value="{{ number_format($totalDamage) }}"
        color="{{ $totalDamage > 0 ? 'red' : 'white' }}" />
</div>

{{-- Unified movements table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-ink text-sm">All Movements</h2>
        <span class="text-xs text-gray-400">{{ $movements->count() }} entries</span>
    </div>

    @if($movements->isEmpty())
        <div class="px-6 py-10 text-center text-sm text-gray-400">No stock movements in this period.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-sand border-b border-gray-100 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Date</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Movement Type</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Change</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Reference / Reason</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($movements as $m)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                        {{ \Carbon\Carbon::instance($m['date'])->format('M j, Y') }}<br>
                        <span class="text-gray-400">{{ \Carbon\Carbon::instance($m['date'])->format('g:i A') }}</span>
                    </td>
                    <td class="px-5 py-3 font-medium text-ink">{{ $m['product'] }}</td>
                    <td class="px-5 py-3">
                        @php
                            $typeColor = match(true) {
                                str_contains($m['type'], 'Sale')     => 'bg-red-100 text-red-700',
                                str_contains($m['type'], 'Restock')  => 'bg-green-100 text-green-700',
                                str_contains($m['type'], 'Damage')   => 'bg-orange-100 text-orange-700',
                                default                              => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium {{ $typeColor }}">
                            {{ $m['type'] }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="font-bold text-base {{ $m['change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $m['change'] > 0 ? '+' : '' }}{{ $m['change'] }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-600 max-w-xs truncate">{{ $m['reason'] }}</td>
                    <td class="px-5 py-3 text-xs text-gray-500">{{ $m['by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

</x-layouts.app>
