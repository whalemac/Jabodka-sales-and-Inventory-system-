<x-layouts.app title="Record Return">
    <div class="mb-6">
        <a href="{{ route('admin.returns.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Returns
        </a>
        <h1 class="text-2xl font-bold text-ink">Record Return</h1>
    </div>
    <div class="max-w-lg space-y-4">
        {{-- Item summary --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-ink text-sm mb-3">Original Sale Item</h2>
            <p class="text-base font-bold text-ink">{{ $salesItem->variant->displayName() }}</p>
            <p class="text-sm text-gray-500 mt-0.5">Qty sold: {{ $salesItem->quantity }} · ₱{{ number_format($salesItem->price_at_sale, 2) }} each</p>
            @if($salesItem->transaction->customer)
            <p class="text-xs text-gray-400 mt-1">Customer: {{ $salesItem->transaction->customer->name }}</p>
            @endif
        </div>

        {{-- Return form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ route('admin.returns.store') }}" x-data="{ type: 'replacement' }" class="space-y-4">
                @csrf
                <input type="hidden" name="sales_item_id" value="{{ $salesItem->id }}">

                <div>
                    <label class="block text-sm font-semibold text-ink mb-2">Return Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="return_type" value="replacement" x-model="type" class="sr-only">
                            <div :class="type === 'replacement' ? 'border-navy bg-navy/5' : 'border-gray-200'"
                                 class="border-2 rounded-xl p-3 text-center transition">
                                <p class="text-sm font-semibold text-ink">Replacement</p>
                                <p class="text-xs text-gray-400">Send new item</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="return_type" value="refund" x-model="type" class="sr-only">
                            <div :class="type === 'refund' ? 'border-navy bg-navy/5' : 'border-gray-200'"
                                 class="border-2 rounded-xl p-3 text-center transition">
                                <p class="text-sm font-semibold text-ink">Refund</p>
                                <p class="text-xs text-gray-400">Return money</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="type === 'refund'" x-cloak>
                    <label class="block text-sm font-semibold text-ink mb-1.5">Refund Amount (₱)</label>
                    <input type="number" name="refund_amount" step="0.01" min="0"
                           value="{{ $salesItem->price_at_sale * $salesItem->quantity }}"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>

                <div x-show="type === 'replacement'" x-cloak>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="restock" value="1" class="w-4 h-4 rounded border-gray-300 text-navy">
                        <span class="text-sm text-gray-700">Restock returned item (add back to inventory)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required placeholder="Describe the reason for return…"
                              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 bg-white text-sm resize-none focus:outline-none focus:border-navy {{ $errors->has('reason') ? 'border-red-400' : '' }}">{{ old('reason') }}</textarea>
                    @error('reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">Record Return</button>
                    <a href="{{ route('admin.returns.index') }}" class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
