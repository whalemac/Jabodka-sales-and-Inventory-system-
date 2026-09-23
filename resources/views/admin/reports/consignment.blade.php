<x-layouts.app title="Consignment Payables Report">

<div class="flex items-center justify-between mb-5 print:mb-3">
    <h1 class="text-2xl font-bold text-ink">Consignment Payables</h1>
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

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-kpi-card label="Pending Payouts"  value="{{ $pendingCount }}" sub="₱{{ number_format($totalPending, 2) }}"  color="{{ $totalPending > 0 ? 'accent' : 'white' }}" />
    <x-kpi-card label="Paid Payouts"     value="{{ $paidCount }}"    sub="₱{{ number_format($totalPaid, 2) }}"     color="green" />
    <x-kpi-card label="Unsettled Sales"  value="{{ $unsettled->count() }}" sub="₱{{ number_format($unsettledAmount, 2) }} owed" color="{{ $unsettled->count() > 0 ? 'red' : 'white' }}" />
    <x-kpi-card label="Total Payable Ever" value="₱{{ number_format($totalPaid + $totalPending, 2) }}" color="white" />
</div>

{{-- Unsettled sales (not yet in any payout) --}}
@if($unsettled->isNotEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-yellow-200 overflow-hidden mb-6">
    <div class="flex items-center gap-3 px-5 py-4 border-b border-yellow-100 bg-yellow-50">
        <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-yellow-800">Unsettled Consignment Sales</p>
            <p class="text-xs text-yellow-700">
                These {{ $unsettled->count() }} {{ Str::plural('sale', $unsettled->count()) }}
                (₱{{ number_format($unsettledAmount, 2) }} total) have not been included in any payout yet.
                <a href="{{ route('admin.consignment.payouts') }}" class="font-semibold underline">Generate payout →</a>
            </p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-sand border-b border-gray-100 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Partner</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Product</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Qty Sold</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Base Price</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Amount Owed</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Sale Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($unsettled as $si)
                @php
                    $ci = $si->variant->consignmentItems->first();
                    $owed = $ci ? $si->quantity * $ci->agreed_base_price : 0;
                @endphp
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3 font-medium text-ink">{{ $ci?->partner->partner_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $si->variant->displayName() }}</td>
                    <td class="px-5 py-3 text-center text-gray-700">{{ $si->quantity }}</td>
                    <td class="px-5 py-3 text-right text-gray-600">₱{{ number_format($ci?->agreed_base_price ?? 0, 2) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-ink">₱{{ number_format($owed, 2) }}</td>
                    <td class="px-5 py-3 text-xs text-gray-500">{{ $si->transaction->transaction_date->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-sand border-t border-gray-200">
                    <td colspan="4" class="px-5 py-3 font-semibold text-ink text-right">Total Unsettled</td>
                    <td class="px-5 py-3 text-right font-bold text-accent">₱{{ number_format($unsettledAmount, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Payout history --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-ink text-sm">Payout History</h2>
    </div>
    @if($payments->isEmpty())
        <div class="px-6 py-10 text-center text-sm text-gray-400">No payouts generated yet.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-sand border-b border-gray-100 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Partner</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Period</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Items</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Amount</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Paid Date</th>
                    <th class="px-5 py-3 print:hidden"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($payments as $payment)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3 font-medium text-ink">{{ $payment->partner->partner_name }}</td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ $payment->period_start->format('M j') }} – {{ $payment->period_end->format('M j, Y') }}
                    </td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $payment->items_count }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-ink">₱{{ number_format($payment->total_amount, 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full
                            {{ $payment->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($payment->payment_status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ $payment->payment_date?->format('M j, Y') ?? '—' }}
                        @if($payment->reference_number)
                        <br><span class="text-gray-400">{{ $payment->reference_number }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right print:hidden">
                        <a href="{{ route('admin.consignment.payouts.show', $payment) }}"
                           class="text-xs text-navy font-medium hover:underline">Details</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-sand border-t border-gray-200">
                    <td colspan="3" class="px-5 py-3 font-semibold text-ink text-right">Totals</td>
                    <td class="px-5 py-3 text-right font-bold text-navy">₱{{ number_format($totalPaid + $totalPending, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

</x-layouts.app>
