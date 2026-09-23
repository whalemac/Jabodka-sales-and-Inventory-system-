<x-layouts.app title="Return #{{ $return->id }}">

    <div class="mb-6">
        <a href="{{ route('admin.returns.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Returns
        </a>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-ink">Return #{{ $return->id }}</h1>
            <span class="inline-block text-sm font-semibold px-3 py-1 rounded-full
                {{ $return->return_type === 'refund' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                {{ ucfirst($return->return_type) }}
            </span>
            @if($return->restocked)
            <span class="inline-flex items-center gap-1 text-sm font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Restocked
            </span>
            @endif
        </div>
        <p class="text-sm text-gray-500 mt-1">
            Recorded on {{ $return->return_date->format('F j, Y · g:i A') }}
            · by {{ $return->user->username }}
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Main: item details + reason --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Returned item --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-sand/40">
                    <h2 class="font-semibold text-ink text-sm">Returned Item</h2>
                </div>
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-ink">{{ $return->salesItem->variant->displayName() }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                Qty: {{ $return->salesItem->quantity }}
                                · ₱{{ number_format($return->salesItem->price_at_sale, 2) }} each
                                · Total: ₱{{ number_format($return->salesItem->lineTotal(), 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reason --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-2">Reason for Return</h2>
                <p class="text-sm text-gray-700 leading-relaxed bg-sand rounded-xl px-4 py-3">
                    {{ $return->reason }}
                </p>
            </div>

            {{-- Outcome --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Outcome</h2>
                <div class="space-y-3">
                    @if($return->return_type === 'refund')
                    <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-xl">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-800">Refund Issued</p>
                            @if($return->refund_amount)
                            <p class="text-lg font-bold text-red-700 mt-0.5">₱{{ number_format($return->refund_amount, 2) }}</p>
                            @else
                            <p class="text-sm text-red-600 mt-0.5">Amount not recorded</p>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-100 rounded-xl">
                        <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">Replacement</p>
                            <p class="text-sm text-blue-600 mt-0.5">New item to be sent to customer.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-4 {{ $return->restocked ? 'bg-green-50 border border-green-100' : 'bg-gray-50 border border-gray-100' }} rounded-xl">
                        @if($return->restocked)
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-green-800">Item Restocked</p>
                            <p class="text-sm text-green-600 mt-0.5">
                                {{ $return->salesItem->quantity }} unit(s) added back to inventory.
                            </p>
                        </div>
                        @else
                        <svg class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-gray-600">Not Restocked</p>
                            <p class="text-sm text-gray-500 mt-0.5">Item was not returned to inventory.</p>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Sidebar: original transaction --}}
        <div class="space-y-4">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Original Transaction</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Transaction #</p>
                        <p class="text-sm font-semibold text-ink">#{{ $return->salesItem->transaction_id }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Channel</p>
                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full mt-0.5
                            {{ $return->salesItem->transaction->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }}">
                            {{ $return->salesItem->transaction->channel === 'walk_in' ? 'Walk-In' : 'Online' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Sale Date</p>
                        <p class="text-sm font-medium text-ink">
                            {{ $return->salesItem->transaction->transaction_date->format('M j, Y · g:i A') }}
                        </p>
                    </div>
                    @if($return->salesItem->transaction->customer)
                    <div>
                        <p class="text-xs text-gray-500">Customer</p>
                        <p class="text-sm font-medium text-ink">{{ $return->salesItem->transaction->customer->name }}</p>
                        @if($return->salesItem->transaction->customer->contact_number)
                        <p class="text-xs text-gray-400">{{ $return->salesItem->transaction->customer->contact_number }}</p>
                        @endif
                    </div>
                    @endif
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-xs text-gray-500">Original Sale Total</p>
                        <p class="text-base font-bold text-ink">
                            ₱{{ number_format($return->salesItem->transaction->grandTotal(), 2) }}
                        </p>
                    </div>
                </div>

                {{-- Link to walk-in index or online show --}}
                @if($return->salesItem->transaction->channel === 'walk_in')
                <a href="{{ route('admin.walk-in.index') }}"
                   class="mt-4 flex items-center gap-2 text-xs text-navy font-medium hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    View Walk-In Orders
                </a>
                @else
                <a href="{{ route('admin.online.show', $return->salesItem->transaction) }}"
                   class="mt-4 flex items-center gap-2 text-xs text-navy font-medium hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    View Online Order
                </a>
                @endif
            </div>

        </div>
    </div>

</x-layouts.app>
