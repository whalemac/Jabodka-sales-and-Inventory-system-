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

    {{-- Persistent "Unsettled / Overdue" KPI cards — every partner's outstanding
         balance, always visible regardless of the paginated detail below. --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
        <div class="flex items-baseline justify-between gap-3 mb-3">
            <div>
                <h2 class="font-semibold text-ink text-sm">Unsettled / Overdue</h2>
                <p class="text-xs text-gray-400 mt-0.5">Consignment sales not yet covered by any payout</p>
            </div>
            <p class="text-xs font-semibold text-gray-500 shrink-0">
                {{ $unsettledCounts->sum() }} {{ Str::plural('sale', $unsettledCounts->sum()) }}
                · ₱{{ number_format($unsettledAmounts->sum(), 2) }}
            </p>
        </div>

        @if($unsettledCounts->isEmpty())
        <div class="flex items-center gap-2 rounded-xl bg-green-50 border border-green-100 px-4 py-3">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-green-700">Nothing outstanding — every consignment sale is settled.</p>
        </div>
        @else
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach($unsettledCounts->sortDesc() as $partnerId => $count)
            @php
                $kpiPartner = $partners->firstWhere('id', $partnerId);
                $kpiAmount = $unsettledAmounts[$partnerId] ?? 0;
            @endphp
            <div class="rounded-xl border-2 {{ $count > 0 ? 'border-accent/30 bg-accent/5' : 'border-gray-100 bg-white' }} p-3">
                <p class="text-xs font-semibold text-ink truncate">{{ $kpiPartner?->partner_name ?? 'Unassigned' }}</p>
                <p class="text-xl font-bold text-ink leading-tight mt-1">{{ $count }}</p>
                <p class="text-[11px] text-gray-500">{{ Str::plural('unsettled sale', $count) }}</p>
                <p class="text-sm font-bold text-accent mt-1">₱{{ number_format($kpiAmount, 2) }}</p>
            </div>
            @endforeach
        </div>
        @endif
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
                                @php $u = $unsettledCounts[$p->id] ?? 0; @endphp
                                @if($u) ({{ $u }} unsettled) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">From</label>
                            <input type="date" name="period_start"
                                   data-validate="required|date" data-field="Period start"
                                   value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">To</label>
                            <input type="date" name="period_end"
                                   data-validate="required|date" data-field="Period end"
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

                @if($unsettled->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">{{ $unsettled->links() }}</div>
                @endif
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
                    <div class="px-5 py-4">
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
                                    <button type="button" onclick="window.Jabodka && window.Jabodka.modalOpen('mark-paid-{{ $payout->id }}')"
                                            class="text-xs text-green-700 font-semibold hover:underline">
                                        Mark Paid
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Mark paid popup form --}}
                        @if($payout->payment_status === 'pending')
                        <x-modal name="mark-paid-{{ $payout->id }}" title="Mark Payout as Paid" subtitle="Pay ₱{{ number_format($payout->total_amount, 2) }} to {{ $payout->partner->partner_name }}." size="md">
                            <form method="POST" action="{{ route('admin.consignment.payouts.mark-paid', $payout) }}"
                                  class="space-y-3">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                                        GCash Reference # <span class="text-gray-400 font-normal">(optional)</span>
                                    </label>
                                    <input type="text" name="reference_number"
                                           placeholder="e.g. 09123456789"
                                           data-label="GCash reference"
                                           data-validate="max:80" data-field="GCash reference"
                                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                </div>
                                <button type="submit"
                                        class="w-full h-11 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
                                    Confirm Paid
                                </button>
                            </form>
                        </x-modal>
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
