<x-layouts.app title="Sales Report">
    <div class="flex items-center justify-between mb-5">
        <div><h1 class="text-2xl font-bold text-ink">Sales Report</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($from)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}</p></div>
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-navy">← Reports</a>
    </div>

    {{-- Range selector --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        @foreach(['today','week','month'] as $r)
        <button type="submit" name="range" value="{{ $r }}"
                class="px-4 py-2 rounded-xl text-sm font-medium transition {{ $range === $r ? 'bg-navy text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
            {{ ucfirst($r) }}
        </button>
        @endforeach
        <div class="flex items-center gap-2 ml-2">
            <input type="date" name="from" value="{{ request('from') }}" class="h-9 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
            <span class="text-gray-400 text-sm">to</span>
            <input type="date" name="to" value="{{ request('to') }}" class="h-9 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
            <button type="submit" name="range" value="custom" class="h-9 px-4 bg-navy text-white rounded-xl text-sm font-medium">Go</button>
        </div>
    </form>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        <x-kpi-card label="Total Revenue" value="₱{{ number_format($totalRevenue, 2) }}" color="navy" />
        <x-kpi-card label="Transactions" value="{{ $totalTransactions }}" color="white" />
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($transactions->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No transactions in this period.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Channel</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Customer</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Items</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Total</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Paid</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($transactions as $t)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $t->transaction_date->format('M j, Y g:i A') }}</td>
                    <td class="px-5 py-3.5"><span class="text-xs px-2 py-0.5 rounded-full {{ $t->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }} font-medium">{{ $t->channel === 'walk_in' ? 'Walk-In' : 'Online' }}</span></td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $t->customer?->name ?? 'Anonymous' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $t->items->count() }}</td>
                    <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($t->grandTotal(), 2) }}</td>
                    <td class="px-5 py-3.5"><span class="text-xs px-2 py-0.5 rounded-full {{ $t->isPaid() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} font-semibold">{{ $t->isPaid() ? 'Yes' : 'No' }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    <div class="mt-3 text-right"><button onclick="window.print()" class="inline-flex items-center gap-2 text-sm text-navy font-medium hover:underline">🖨 Print / Save PDF</button></div>
</x-layouts.app>
