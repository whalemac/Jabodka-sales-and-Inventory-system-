<x-layouts.app title="Best Sellers">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold text-ink">Best Sellers</h1>
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-navy">← Reports</a>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        @foreach(['today','week','month'] as $r)
        <button type="submit" name="range" value="{{ $r }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ $range === $r ? 'bg-navy text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">{{ ucfirst($r) }}</button>
        @endforeach
    </form>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($items->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No sales data for this period.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">#</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Units Sold</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Revenue</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($items as $i => $item)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 text-gray-400 font-semibold">{{ $i + 1 }}</td>
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $item->variant->displayName() }}</td>
                    <td class="px-5 py-3.5 font-bold text-ink">{{ $item->total_sold }}</td>
                    <td class="px-5 py-3.5 text-gray-700">₱{{ number_format($item->total_revenue, 2) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-layouts.app>
