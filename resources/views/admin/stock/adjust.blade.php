<x-layouts.app title="Adjust Stock — {{ $variant->displayName() }}">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Manual Stock Adjustment</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $variant->displayName() }}</p>
    </div>

    <div class="max-w-lg grid grid-cols-1 gap-5">

        {{-- Current stock card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0
                {{ $variant->stock_count === 0 ? 'bg-red-100' : ($variant->isLowStock() ? 'bg-yellow-100' : 'bg-green-100') }}">
                <span class="text-2xl font-bold {{ $variant->stock_count === 0 ? 'text-red-600' : ($variant->isLowStock() ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $variant->stock_count }}
                </span>
            </div>
            <div>
                <p class="font-bold text-ink">{{ $variant->product->name }}</p>
                <p class="text-sm text-gray-500">{{ $variant->label() }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Reorder level: {{ $variant->reorder_level }}</p>
                @if($variant->isLowStock())
                <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full
                    {{ $variant->stock_count === 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $variant->stock_count === 0 ? 'Out of stock' : '⚠ Low stock' }}
                </span>
                @endif
            </div>
        </div>

        {{-- Adjustment form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ route('admin.stock.adjust', $variant) }}"
                  x-data="{ qty: 0, type: 'correction', preview: {{ $variant->stock_count }} }"
                  @submit.prevent="$el.submit()">
                @csrf

                {{-- Type --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-ink mb-2">Adjustment Type</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            ['value' => 'correction',         'label' => 'Correction',    'desc' => 'Fix count error'],
                            ['value' => 'damage',             'label' => 'Damage/Loss',   'desc' => 'Damaged items'],
                            ['value' => 'consignment_return', 'label' => 'Consign. Return','desc' => 'Returned goods'],
                        ] as $opt)
                        <label class="cursor-pointer" x-on:click="type = '{{ $opt['value'] }}'">
                            <input type="radio" name="adjustment_type" value="{{ $opt['value'] }}"
                                   x-model="type" class="sr-only" {{ old('adjustment_type', 'correction') === $opt['value'] ? 'checked' : '' }}>
                            <div :class="type === '{{ $opt['value'] }}' ? 'border-navy bg-navy/5' : 'border-gray-200 hover:border-gray-300'"
                                 class="border-2 rounded-xl p-3 transition text-center">
                                <p class="text-xs font-semibold text-ink">{{ $opt['label'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $opt['desc'] }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('adjustment_type')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Quantity --}}
                <div class="mb-5" x-data>
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Quantity Change
                        <span class="text-gray-400 font-normal text-xs">(use negative to reduce, e.g. -3)</span>
                    </label>
                    <input type="number" name="quantity_changed"
                           x-model.number="qty"
                           @input="preview = {{ $variant->stock_count }} + (parseInt($event.target.value) || 0)"
                           value="{{ old('quantity_changed', 0) }}"
                           placeholder="e.g. -2 or +5"
                           class="w-full h-11 px-4 rounded-xl border-2 text-sm focus:outline-none focus:border-navy
                                  {{ $errors->has('quantity_changed') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('quantity_changed')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror

                    {{-- Live preview --}}
                    <div x-show="qty !== 0" x-cloak class="mt-3 flex items-center gap-3 bg-sand rounded-xl px-4 py-3">
                        <span class="text-sm text-gray-600">New stock level:</span>
                        <span class="text-lg font-bold"
                              :class="preview < 0 ? 'text-red-600' : (preview <= {{ $variant->reorder_level }} ? 'text-yellow-600' : 'text-green-600')"
                              x-text="preview">
                        </span>
                        <span x-show="preview < 0" class="text-xs text-red-600 font-semibold">(cannot go below 0)</span>
                        <span x-show="preview >= 0 && preview <= {{ $variant->reorder_level }}" x-cloak class="text-xs text-yellow-600 font-semibold">⚠ still low</span>
                    </div>
                </div>

                {{-- Reason --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-ink mb-1.5">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required
                              placeholder="Briefly describe why this adjustment is needed…"
                              class="w-full px-4 py-3 rounded-xl border-2 text-sm resize-none focus:outline-none focus:border-navy
                                     {{ $errors->has('reason') ? 'border-red-400' : 'border-gray-200' }}">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            :disabled="qty === 0"
                            class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Save Adjustment
                    </button>
                    <a href="{{ route('admin.stock.index') }}"
                       class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
