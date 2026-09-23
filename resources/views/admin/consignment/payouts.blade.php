<x-layouts.app title="Consignment Payouts">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.consignment.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Consignment
            </a>
            <h1 class="text-2xl font-bold text-ink">Consignment Payouts</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ===== LEFT: Generate payout + unsettled ===== --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Generate form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Generate New Payout</h2>
                <form method="POST" action="{{ route('admin.consignment.payouts.generate') }}"
                      class="space-y-3">
                    @csrf

                    @error('partner_id')
                    <div class="bg-red-50 border border-red-200 rounded-xl px-3 py-2.5 text-xs text-red-700">
                        {{ $message }}
                    </div>
                    @enderror

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Partner</label>
                        <select name="partner_id" required
                                class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">Select partner…</option>
                            @foreach($partners as $p)
                            <option value="{{ $p->id }}"
                                {{ (request('partner_id') == $p->id || old('partner_id') == $p->id) ? 'selected' : '' }}>
                                {{ $p->partner_name }}
                                @php $u = $unsettled[$p->id] ?? null; @endphp
                                @if($u && $u->count()) ({{ $u->count() }} unsettled) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">From</label>
                            <input type="date" name="period_start" required
                                   value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">To</label>
                            <input type="date" name="period_end" required
                                   value="{{ old('period_end', now()->format('Y-m-d')) }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                    </div>
                    <button type="submit"
                            class="w-full h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        Generate Payout
                    </button>
                    <p class="text-xs text-gray-400 text-center">
                        Only unsettled sales within the period will be included.
                    </p>
                </form>
            </div>

            {{-- Unsettled sales summary --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Unsettled Sales</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Consignment sales not yet included in any payout</p>
                </div>

                @if($unsettled->isEmpty())
                <div class="px-5 py-8 text-center">
                    <svg class="w-8 h-8 text-green-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400">All consignment sales are settled.</p>
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($unsettled as $partnerId => $items)
                    @php
                        $partnerName = $items->first()->variant->consignmentItems->first()?->partner?->partner_name ?? 'Unknown Partner';
                        $totalOwed = $items->sum(fn($si) => (float)($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity);
                    @endphp
                    <div class="px-5 py-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink">{{ $partnerName }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $items->count() }} unsettled {{ Str::plural('sale', $items->count()) }}
                                </p>
                            </div>
                            <p class="text-sm font-bold text-accent shrink-0">
                                ₱{{ number_format($totalOwed, 2) }}
                            </p>
                        </div>
                        {{-- Item breakdown --}}
                        <div class="mt-2 space-y-1">
                            @foreach($items->take(3) as $si)
                            <div class="flex justify-between text-xs text-gray-500">
                                <span class="truncate">{{ $si->variant->displayName() }} × {{ $si->quantity }}</span>
                                <span class="ml-2 shrink-0">
                                    ₱{{ number_format((float)($si->variant->consignmentItems->first()?->agreed_base_price ?? 0) * $si->quantity, 2) }}
                                </span>
                            </div>
                            @endforeach
                            @if($items->count() > 3)
                            <p class="text-xs text-gray-400">+ {{ $items->count() - 3 }} more…</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ===== RIGHT: Payout history ===== --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Payout History</h2>
                </div>

                @if($payments->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-400">
                    No payouts generated yet.
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($payments as $payout)
                    <div class="px-5 py-4" x-data="{ markingPaid: false }">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold text-ink text-sm">{{ $payout->partner->partner_name }}</p>
                                    @if($payout->payment_status === 'paid')
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Paid
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                        Pending
                                    </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $payout->period_start->format('M j') }} – {{ $payout->period_end->format('M j, Y') }}
                                    · {{ $payout->items_count }} {{ Str::plural('item', $payout->items_count) }}
                                </p>
                                @if($payout->reference_number)
                                <p class="text-xs text-gray-400 mt-0.5">GCash ref: {{ $payout->reference_number }}</p>
                                @endif
                                @if($payout->payment_date)
                                <p class="text-xs text-gray-400">Paid: {{ $payout->payment_date->format('M j, Y') }}</p>
                                @endif
                            </div>

                            <div class="text-right shrink-0">
                                <p class="text-lg font-bold text-ink">₱{{ number_format($payout->total_amount, 2) }}</p>
                                <div class="flex items-center gap-2 justify-end mt-1">
                                    <a href="{{ route('admin.consignment.payouts.show', $payout) }}"
                                       class="text-xs text-navy font-medium hover:underline">Details</a>
                                    @if($payout->payment_status === 'pending')
                                    <button @click="markingPaid = !markingPaid"
                                            class="text-xs text-green-700 font-semibold hover:underline">
                                        Mark Paid
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Mark paid inline form --}}
                        @if($payout->payment_status === 'pending')
                        <div x-show="markingPaid" x-cloak class="mt-3 pt-3 border-t border-gray-100">
                            <form method="POST" action="{{ route('admin.consignment.payouts.mark-paid', $payout) }}"
                                  class="flex flex-col sm:flex-row gap-2">
                                @csrf @method('PATCH')
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                                        GCash Reference # <span class="text-gray-400 font-normal">(optional)</span>
                                    </label>
                                    <input type="text" name="reference_number"
                                           placeholder="e.g. 09123456789"
                                           class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                </div>
                                <div class="flex gap-2 items-end">
                                    <button type="submit"
                                            class="h-10 px-4 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
                                        Confirm Paid
                                    </button>
                                    <button type="button" @click="markingPaid = false"
                                            class="h-10 px-3 bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @if($payments->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">{{ $payments->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>

</x-layouts.app>
