<x-layouts.app title="Returns & Replacements">
    <h1 class="text-2xl font-bold text-ink mb-6">Returns & Replacements</h1>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($returns->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No returns recorded yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Item</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Type</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Reason</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Refund</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Restocked</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($returns as $return)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $return->return_date->format('M j, Y') }}</td>
                    <td class="px-5 py-3.5 text-sm font-medium text-ink">{{ $return->salesItem->variant->displayName() }}</td>
                    <td class="px-5 py-3.5">
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $return->return_type === 'refund' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }} font-semibold">
                            {{ ucfirst($return->return_type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-600 max-w-xs truncate">{{ $return->reason }}</td>
                    <td class="px-5 py-3.5 text-sm">{{ $return->refund_amount ? '₱'.number_format($return->refund_amount, 2) : '—' }}</td>
                    <td class="px-5 py-3.5">
                        @if($return->restocked)
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $returns->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
