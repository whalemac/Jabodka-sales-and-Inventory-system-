<x-layouts.app title="Stock Movement">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold text-ink">Stock Movement</h1>
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-navy">← Reports</a>
    </div>
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        @foreach(['today','week','month'] as $r)
        <button type="submit" name="range" value="{{ $r }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ $range === $r ? 'bg-navy text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">{{ ucfirst($r) }}</button>
        @endforeach
    </form>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($adjustments->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No stock adjustments in this period.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Product</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Type</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Change</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Reason</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">By</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($adjustments as $adj)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $adj->created_at->format('M j, Y') }}</td>
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $adj->variant->displayName() }}</td>
                    <td class="px-5 py-3.5 text-xs"><span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium">{{ ucfirst(str_replace('_', ' ', $adj->adjustment_type)) }}</span></td>
                    <td class="px-5 py-3.5 font-bold {{ $adj->quantity_changed > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $adj->quantity_changed > 0 ? '+' : '' }}{{ $adj->quantity_changed }}
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 max-w-xs truncate">{{ $adj->reason }}</td>
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $adj->user->username }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $adjustments->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
