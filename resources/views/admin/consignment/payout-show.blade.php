<x-layouts.app title="Payout — {{ $payment->partner->partner_name }}">

    <div class="mb-6">
        <a href="{{ route('admin.consignment.payouts') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Payouts
        </a>
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink">Payout #{{ $payment->id }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $payment->partner->partner_name }}
                    · {{ $payment->period_start->format('M j') }} – {{ $payment->period_end->format('M j, Y') }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-1.5 rounded-full self-start
                {{ $payment->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                <span class="w-2 h-2 rounded-full {{ $payment->payment_status === 'paid' ? 'bg-green-500' : 'bg-yellow-500' }}"></span>
                {{ $payment->payment_status === 'paid' ? 'Paid' : 'Pending Payment' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Item breakdown --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Items in This Payout</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $payment->items->count() }} line {{ Str::plural('item', $payment->items->count()) }}
                        · partner_amount = quantity × agreed_base_price
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-sand border-b border-gray-100 text-left">
                                <th class="px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-center">Qty Sold</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Base Price</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Partner Amount</th>
                                <th class="px-5 py-3 font-semibold text-gray-600">Sale Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($payment->items as $line)
                            <tr class="hover:bg-sand/50 transition">
                                <td class="px-5 py-3.5 font-medium text-ink">
                                    {{ $line->consignmentItem->variant->displayName() }}
                                </td>
                                <td class="px-5 py-3.5 text-center text-gray-700">{{ $line->quantity }}</td>
                                <td class="px-5 py-3.5 text-right text-gray-600">
                                    ₱{{ number_format($line->consignmentItem->agreed_base_price, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-semibold text-ink">
                                    ₱{{ number_format($line->partner_amount, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">
                                    {{ $line->salesItem->transaction->transaction_date->format('M j, Y') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-sand border-t border-gray-200">
                                <td colspan="3" class="px-5 py-3.5 font-semibold text-ink text-right">Total Payout</td>
                                <td class="px-5 py-3.5 font-bold text-lg text-ink text-right">
                                    ₱{{ number_format($payment->total_amount, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Summary + mark paid --}}
        <div class="space-y-4">

            {{-- Summary card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Payout Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Partner</span>
                        <span class="font-medium text-ink">{{ $payment->partner->partner_name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Period</span>
                        <span class="font-medium text-ink text-right">
                            {{ $payment->period_start->format('M j') }} –<br>
                            {{ $payment->period_end->format('M j, Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Items</span>
                        <span class="font-medium text-ink">{{ $payment->items->count() }}</span>
                    </div>
                    @if($payment->reference_number)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">GCash Ref</span>
                        <span class="font-medium text-ink">{{ $payment->reference_number }}</span>
                    </div>
                    @endif
                    @if($payment->payment_date)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Paid On</span>
                        <span class="font-medium text-ink">{{ $payment->payment_date->format('M j, Y') }}</span>
                    </div>
                    @endif
                    @if($payment->user)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Generated by</span>
                        <span class="font-medium text-ink">{{ $payment->user->username }}</span>
                    </div>
                    @endif
                    <div class="pt-2 border-t border-gray-100 flex justify-between">
                        <span class="font-semibold text-ink">Total Amount</span>
                        <span class="text-xl font-bold text-navy">₱{{ number_format($payment->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Mark paid (if pending) --}}
            @if($payment->payment_status === 'pending')
            <div class="bg-white rounded-2xl shadow-sm border border-yellow-200 p-5"
                 x-data="{ open: false }">
                <h2 class="font-semibold text-ink text-sm mb-1">Record Payment</h2>
                <p class="text-xs text-gray-500 mb-4">
                    Mark this payout as paid once you've transferred ₱{{ number_format($payment->total_amount, 2) }} to {{ $payment->partner->partner_name }}.
                </p>
                <form method="POST" action="{{ route('admin.consignment.payouts.mark-paid', $payment) }}"
                      class="space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            GCash Reference # <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="text" name="reference_number"
                               placeholder="e.g. 09123456789"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <button type="submit"
                            class="w-full h-11 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-xl transition">
                        ✓ Mark as Paid
                    </button>
                </form>
            </div>
            @else
            <div class="bg-green-50 border border-green-200 rounded-2xl p-5 text-center">
                <svg class="w-8 h-8 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold text-green-800">Payment Completed</p>
                <p class="text-xs text-green-700 mt-1">
                    ₱{{ number_format($payment->total_amount, 2) }} paid
                    @if($payment->payment_date) on {{ $payment->payment_date->format('M j, Y') }} @endif
                </p>
            </div>
            @endif

            {{-- Print button --}}
            <button onclick="window.print()"
                    class="w-full h-11 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium rounded-xl transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print / Save PDF
            </button>
        </div>
    </div>

</x-layouts.app>
