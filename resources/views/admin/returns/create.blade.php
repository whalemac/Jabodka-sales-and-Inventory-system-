<x-layouts.app title="Record Return">

    <div class="mb-6">
        <a href="{{ route('admin.returns.search') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Transaction Search
        </a>
        <h1 class="text-2xl font-bold text-ink">Record Return</h1>
    </div>

    <div class="max-w-lg space-y-4"
         x-data="{
            type: '{{ old('return_type', 'replacement') }}',
            restock: {{ old('restock') ? 'true' : 'false' }},
            refundAmount: {{ old('refund_amount', $salesItem->price_at_sale * $salesItem->quantity) }},
            maxRefund: {{ $salesItem->price_at_sale * $salesItem->quantity }},
         }">

        {{-- Original sale item summary --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-sand/40">
                <h2 class="font-semibold text-ink text-sm">Original Sale</h2>
            </div>
            <div class="px-5 py-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-bold text-ink">{{ $salesItem->variant->displayName() }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Qty: {{ $salesItem->quantity }}
                            · ₱{{ number_format($salesItem->price_at_sale, 2) }} each
                            · Total: ₱{{ number_format($salesItem->lineTotal(), 2) }}
                        </p>
                    </div>
                    <span class="inline-block text-xs px-2.5 py-1 rounded-full font-medium shrink-0
                        {{ $salesItem->transaction->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }}">
                        {{ $salesItem->transaction->channel === 'walk_in' ? 'Walk-In' : 'Online' }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3 pt-1 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-500">Transaction</p>
                        <p class="text-sm font-medium text-ink">#{{ $salesItem->transaction_id }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Date Sold</p>
                        <p class="text-sm font-medium text-ink">{{ $salesItem->transaction->transaction_date->format('M j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Customer</p>
                        <p class="text-sm font-medium text-ink">{{ $salesItem->transaction->customer?->name ?? 'Anonymous' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Contact</p>
                        <p class="text-sm font-medium text-ink">{{ $salesItem->transaction->customer?->contact_number ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Return form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ route('admin.returns.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="sales_item_id" value="{{ $salesItem->id }}">

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
                @endif

                {{-- Return type --}}
                <div>
                    <label class="block text-sm font-semibold text-ink mb-2">Return Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label @click="type = 'replacement'" class="cursor-pointer">
                            <input type="radio" name="return_type" value="replacement" x-model="type" class="sr-only">
                            <div :class="type === 'replacement'
                                    ? 'border-blue-400 bg-blue-50'
                                    : 'border-gray-200 hover:border-gray-300'"
                                 class="border-2 rounded-xl p-4 transition">
                                <div class="flex items-center gap-2.5 mb-1">
                                    <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-ink">Replacement</p>
                                </div>
                                <p class="text-xs text-gray-500 ml-7.5">Customer gets a new item sent</p>
                            </div>
                        </label>

                        <label @click="type = 'refund'" class="cursor-pointer">
                            <input type="radio" name="return_type" value="refund" x-model="type" class="sr-only">
                            <div :class="type === 'refund'
                                    ? 'border-red-400 bg-red-50'
                                    : 'border-gray-200 hover:border-gray-300'"
                                 class="border-2 rounded-xl p-4 transition">
                                <div class="flex items-center gap-2.5 mb-1">
                                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-ink">Refund</p>
                                </div>
                                <p class="text-xs text-gray-500 ml-7.5">Customer gets money back</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Refund amount (refund only) --}}
                <div x-show="type === 'refund'" x-cloak class="space-y-2">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Refund Amount (₱)
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-400 text-sm">₱</span>
                        <input type="number" name="refund_amount"
                               x-model.number="refundAmount"
                               step="0.01" min="0"
                               :max="maxRefund"
                               class="w-full h-11 pl-8 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy
                                      {{ $errors->has('refund_amount') ? 'border-red-400' : '' }}">
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Max refund (full sale): ₱{{ number_format($salesItem->lineTotal(), 2) }}</span>
                        <button type="button" @click="refundAmount = maxRefund"
                                class="text-navy font-medium hover:underline">Use full amount</button>
                    </div>
                    @error('refund_amount')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Restock option (replacement only) --}}
                <div x-show="type === 'replacement'" x-cloak>
                    <div class="flex items-start gap-3 bg-sand rounded-xl px-4 py-3 cursor-pointer"
                         @click="restock = !restock">
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center mt-0.5 shrink-0 transition"
                             :class="restock ? 'bg-navy border-navy' : 'border-gray-300 bg-white'">
                            <svg x-show="restock" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <input type="checkbox" name="restock" value="1" x-model="restock" class="sr-only">
                        <div>
                            <p class="text-sm font-semibold text-ink">Return item to inventory</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Check this if the customer physically returns the item and it can be re-sold.
                                Stock count for <strong>{{ $salesItem->variant->displayName() }}</strong> will be
                                increased by {{ $salesItem->quantity }}.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Reason --}}
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" rows="3" required
                              placeholder="Describe the reason for this return…"
                              class="w-full px-4 py-3 rounded-xl border-2 text-sm resize-none focus:outline-none focus:border-navy
                                     {{ $errors->has('reason') ? 'border-red-400' : 'border-gray-200' }}">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">
                        This is saved to the activity log for owner review.
                    </p>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit"
                            class="flex-1 h-12 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition shadow-sm">
                        <span x-text="type === 'refund' ? 'Record Refund' : 'Record Replacement'">Record Return</span>
                    </button>
                    <a href="{{ route('admin.returns.search') }}"
                       class="flex-1 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Current stock info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Current stock of this variant</p>
                <p class="text-sm font-semibold text-ink">{{ $salesItem->variant->displayName() }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold
                    {{ $salesItem->variant->stock_count === 0 ? 'text-red-600' : ($salesItem->variant->isLowStock() ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $salesItem->variant->stock_count }}
                </p>
                <p class="text-xs text-gray-400">in stock</p>
            </div>
        </div>

    </div>

</x-layouts.app>
