<x-layouts.app title="New Return — Find Transaction">

    <div class="mb-6">
        <a href="{{ route('admin.returns.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Returns
        </a>
        <h1 class="text-2xl font-bold text-ink">New Return — Find Transaction</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Search by customer name, product name, or transaction ID to find the original sale.
        </p>
    </div>

    {{-- Search form --}}
    <form method="GET" action="{{ route('admin.returns.search') }}" class="mb-6">
        <div class="flex gap-3">
            <div class="flex-1 relative">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="Customer name, product name, or transaction ID…"
                       autofocus
                       class="w-full h-12 pl-10 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="submit"
                    class="h-12 px-6 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                Search
            </button>
        </div>
    </form>

    @if($q === '' && $transactions->isEmpty())
        {{-- Initial empty state --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-16 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="text-sm text-gray-400">Enter a customer name, product name, or transaction # above to find the sale.</p>
        </div>
    @elseif($transactions->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-12 text-center">
            <p class="text-sm text-gray-400">No transactions found for "{{ $q }}".</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($transactions as $tx)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ open: true }">

                {{-- Transaction header --}}
                <div class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-sand/50 transition"
                     @click="open = !open">
                    <div class="flex items-start gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center
                            {{ $tx->channel === 'walk_in' ? 'bg-navy/10' : 'bg-accent/15' }}">
                            @if($tx->channel === 'walk_in')
                            <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-accent-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-ink">Transaction #{{ $tx->id }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ $tx->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }}">
                                    {{ $tx->channel === 'walk_in' ? 'Walk-In' : 'Online' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $tx->transaction_date->format('M j, Y · g:i A') }}
                                · {{ $tx->customer?->name ?? 'Anonymous' }}
                                · ₱{{ number_format($tx->grandTotal(), 2) }}
                            </p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition ml-3" :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                {{-- Items list --}}
                <div x-show="open" class="border-t border-gray-100">
                    <div class="divide-y divide-gray-50">
                        @foreach($tx->items as $item)
                        @php $hasReturn = $item->returns->isNotEmpty(); @endphp
                        <div class="flex items-center justify-between px-5 py-3.5
                            {{ $hasReturn ? 'bg-gray-50/70 opacity-75' : '' }}">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink">{{ $item->variant->displayName() }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    ₱{{ number_format($item->price_at_sale, 2) }} × {{ $item->quantity }}
                                    = ₱{{ number_format($item->lineTotal(), 2) }}
                                </p>
                            </div>
                            <div class="ml-4 shrink-0">
                                @if($hasReturn)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-500">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Already Returned
                                    </span>
                                @else
                                    <a href="{{ route('admin.returns.create', $item) }}"
                                       class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-xl bg-navy text-white hover:bg-navy-dark transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        Return This
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach

            @if($transactions->hasPages())
            <div class="pt-2">{{ $transactions->links() }}</div>
            @endif
        </div>
    @endif

</x-layouts.app>
