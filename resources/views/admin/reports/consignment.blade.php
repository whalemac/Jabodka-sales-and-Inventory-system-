<x-layouts.app title="Consignment Payables Report">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold text-ink">Consignment Payables</h1>
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-navy">← Reports</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($payments->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No consignment payments generated yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Partner</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Period</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Items</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Amount</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Paid Date</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($payments as $payment)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $payment->partner->partner_name }}</td>
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $payment->period_start->format('M j') }} – {{ $payment->period_end->format('M j, Y') }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $payment->items->count() }}</td>
                    <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($payment->total_amount, 2) }}</td>
                    <td class="px-5 py-3.5"><span class="inline-block text-xs px-2.5 py-0.5 rounded-full {{ $payment->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} font-semibold">{{ ucfirst($payment->payment_status) }}</span></td>
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $payment->payment_date?->format('M j, Y') ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $payments->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
