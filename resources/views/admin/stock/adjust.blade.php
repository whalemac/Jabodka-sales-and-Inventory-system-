<x-layouts.app title="Adjust Stock — {{ $variant->displayName() }}">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Manual Stock Adjustment</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Use this for corrections, damage, or consignment returns — not for supplier deliveries.
            <a href="{{ route('admin.stock.import-form', ['variant' => $variant->id]) }}"
               class="text-navy font-medium hover:underline">Use Import for deliveries →</a>
        </p>
    </div>

    <div class="max-w-lg space-y-5">

        {{-- Current stock summary card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-5">
            @php
                $isOut = $variant->stock_count === 0;
                $isLow = !$isOut && $variant->isLowStock();
            @endphp
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0
                {{ $isOut ? 'bg-red-100' : ($isLow ? 'bg-yellow-100' : 'bg-green-100') }}">
                <span class="text-2xl font-bold
                    {{ $isOut ? 'text-red-600' : ($isLow ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $variant->stock_count }}
                </span>
            </div>
            <div>
                <p class="font-bold text-ink">{{ $variant->product->name }}</p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $variant->label() }}</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <span class="text-xs text-gray-400">Reorder level: {{ $variant->reorder_level }}</span>
                    @if($isOut)
                        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Out of stock</span>
                    @elseif($isLow)
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">⚠ Low stock</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Adjustment form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
             x-data="{
                qty: {{ old('quantity_changed', 0) }},
                type: '{{ old('adjustment_type', 'correction') }}',
                preview: {{ $variant->stock_count }},
                reorder: {{ $variant->reorder_level }},
                updatePreview(val) {
                    this.preview = {{ $variant->stock_count }} + (parseInt(val) || 0);
                }
             }">

            <form method="POST" action="{{ route('admin.stock.adjust', $variant) }}">
                @csrf

                {{-- Adjustment type --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-ink mb-2">Adjustment Type</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            ['value' => 'correction',          'label' => 'Correction',      'desc' => 'Fix a count error', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                            ['value' => 'damage',               'label' => 'Damage / Loss',   'desc' => 'Damaged or lost items', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                            ['value' => 'consignment_return',  'label' => 'Consign. Return', 'desc' => 'Items returned by partner', 'icon' => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
                        ] as $opt)
                        <label class="cursor-pointer" @click="type = '{{ $opt['value'] }}'">
                            <input type="radio" name="adjustment_type" value="{{ $opt['value'] }}"
                                   x-model="type" class="sr-only">
                            <div :class="type === '{{ $opt['value'] }}' ? 'border-navy bg-navy/5 text-navy' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                 class="border-2 rounded-xl p-3 transition text-center h-full">
                                <svg class="w-5 h-5 mx-auto mb-1.5 {{ old('adjustment_type', 'correction') === $opt['value'] ? 'text-navy' : 'text-gray-400' }}"
                                     :class="type === '{{ $opt['value'] }}' ? 'text-navy' : 'text-gray-400'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $opt['icon'] }}"/>
                                </svg>
                                <p class="text-xs font-semibold leading-tight">{{ $opt['label'] }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $opt['desc'] }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('adjustment_type')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Quantity changed --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Quantity Change
                        <span class="text-xs text-gray-400 font-normal ml-1">
                            (positive = add · negative = remove, e.g. −3)
                        </span>
                    </label>
                    <input type="number" name="quantity_changed"
                           x-model.number="qty"
                           @input="updatePreview($event.target.value)"
                           value="{{ old('quantity_changed', 0) }}"
                           placeholder="0"
                           class="w-full h-12 px-4 rounded-xl border-2 text-sm text-center font-bold focus:outline-none focus:border-navy
                                  {{ $errors->has('quantity_changed') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('quantity_changed')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror

                    {{-- Live result preview --}}
                    <div x-show="qty !== 0" x-cloak
                         class="mt-3 rounded-xl px-4 py-3 border-2 transition"
                         :class="preview < 0 ? 'bg-red-50 border-red-200' : (preview <= reorder ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200')">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">New stock level</span>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold"
                                      :class="preview < 0 ? 'text-red-600' : (preview <= reorder ? 'text-yellow-600' : 'text-green-600')"
                                      x-text="preview">
                                </span>
                                <span x-show="preview < 0"
                                      class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">
                                    Cannot go below 0
                                </span>
                                <span x-show="preview >= 0 && preview <= reorder" x-cloak
                                      class="text-xs font-semibold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full">
                                    Still low
                                </span>
                                <span x-show="preview > reorder" x-cloak
                                      class="text-xs font-semibold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                    ✓ Healthy
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden" x-show="preview >= 0">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="preview <= reorder ? 'bg-yellow-400' : 'bg-green-500'"
                                 :style="'width: ' + Math.min(100, Math.max(2, (preview / Math.max(preview, reorder * 2)) * 100)) + '%'">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reason --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" rows="3" required
                              placeholder="Describe why this adjustment is being made…"
                              class="w-full px-4 py-3 rounded-xl border-2 text-sm resize-none focus:outline-none focus:border-navy
                                     {{ $errors->has('reason') ? 'border-red-400' : 'border-gray-200' }}">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">
                        This reason is saved to the activity log and is required for audit purposes.
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            x-bind:disabled="qty === 0 || preview < 0"
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
