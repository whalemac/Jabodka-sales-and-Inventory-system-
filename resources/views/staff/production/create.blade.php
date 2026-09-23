<x-layouts.app title="Log Production">

    <div class="mb-6">
        <a href="{{ route('staff.production.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Production Log
        </a>
        <h1 class="text-2xl font-bold text-ink">Log Production</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Record finished handmade items you produced today.
        </p>
    </div>

    @if($variants->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 text-center">
        <svg class="w-10 h-10 text-yellow-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-sm font-semibold text-yellow-800">No handmade products set up yet.</p>
        <p class="text-xs text-yellow-700 mt-1">Ask the Owner (Admin) to add handmade products in Product Management first.</p>
    </div>
    @else

    {{-- Alpine component — rename the PHP $materials var to avoid collision --}}
    @php $rawMaterialsList = $materials; @endphp

    <div class="max-w-2xl"
         x-data="{
            usedMaterials: [],
            submitting: false,
            showConfirm: false,
            variantName: '',
            qtyProduced: 1,

            updatePreview(sel) {
                this.variantName = sel.options[sel.selectedIndex]?.text?.split(' (')[0] ?? '';
            },
         }">

        <form method="POST" action="{{ route('staff.production.store') }}"
              id="prod-form"
              @submit.prevent="showConfirm = true">
            @csrf

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- ===== Step 1: What did you make? ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-navy flex items-center justify-center shrink-0">
                        <span class="text-white text-sm font-bold">1</span>
                    </div>
                    <h2 class="font-semibold text-ink">What did you make?</h2>
                </div>

                <div class="space-y-4">
                    {{-- Product selector --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Product <span class="text-red-500">*</span>
                        </label>
                        @php
                            $grouped = $variants->groupBy(fn($v) => $v->product->name);
                        @endphp
                        <select name="variant_id" required
                                @change="updatePreview($event.target)"
                                class="w-full h-12 px-4 rounded-xl border-2 text-sm focus:outline-none focus:border-navy
                                       {{ $errors->has('variant_id') ? 'border-red-400' : 'border-gray-200' }}">
                            <option value="">— Tap to choose product —</option>
                            @foreach($grouped as $productName => $group)
                                @if($group->count() === 1 && !$group->first()->size && !$group->first()->version)
                                    {{-- Single variant with no label --}}
                                    <option value="{{ $group->first()->id }}"
                                        {{ old('variant_id') == $group->first()->id ? 'selected' : '' }}>
                                        {{ $productName }}
                                        ({{ $group->first()->stock_count }} in stock)
                                    </option>
                                @else
                                    <optgroup label="{{ $productName }}">
                                        @foreach($group as $v)
                                        <option value="{{ $v->id }}"
                                            {{ old('variant_id') == $v->id ? 'selected' : '' }}>
                                            {{ $v->label() }}
                                            ({{ $v->stock_count }} in stock)
                                        </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @error('variant_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Quantity --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                How many did you finish? <span class="text-red-500">*</span>
                            </label>
                            <input name="quantity_produced" type="number" min="1" required
                                   x-model.number="qtyProduced"
                                   value="{{ old('quantity_produced', 1) }}"
                                   class="w-full h-12 px-4 rounded-xl border-2 text-lg font-bold text-center focus:outline-none focus:border-navy
                                          {{ $errors->has('quantity_produced') ? 'border-red-400' : 'border-gray-200' }}">
                            @error('quantity_produced')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Production Date <span class="text-red-500">*</span>
                            </label>
                            <input name="production_date" type="date" required
                                   value="{{ old('production_date', now()->format('Y-m-d')) }}"
                                   max="{{ now()->format('Y-m-d') }}"
                                   class="w-full h-12 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes (optional)</label>
                        <textarea name="notes" rows="2"
                                  placeholder="Anything to note about this batch…"
                                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 text-sm resize-none focus:outline-none focus:border-navy">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ===== Step 2: Raw materials used ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-navy flex items-center justify-center shrink-0">
                                <span class="text-white text-sm font-bold">2</span>
                            </div>
                            <div>
                                <h2 class="font-semibold text-ink">Materials Used</h2>
                                <p class="text-xs text-gray-400">Add the raw materials you used for this batch</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="usedMaterials.push({ id: '', qty: '' })"
                                class="inline-flex items-center gap-1.5 text-sm text-navy font-semibold hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add
                        </button>
                    </div>
                </div>

                {{-- Empty state --}}
                <div x-show="usedMaterials.length === 0" class="px-5 py-8 text-center">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-sm text-gray-400">
                        No materials added.
                        @if($rawMaterialsList->isEmpty())
                            <a href="{{ route('staff.materials.index') }}" class="text-navy font-medium hover:underline">Add raw materials first →</a>
                        @else
                            Tap "+ Add" above if you used any materials.
                        @endif
                    </p>
                </div>

                {{-- Material rows --}}
                <div class="divide-y divide-gray-50" x-show="usedMaterials.length > 0">
                    <template x-for="(m, idx) in usedMaterials" :key="idx">
                        <div class="px-5 py-4">
                            <div class="grid grid-cols-1 sm:grid-cols-7 gap-3 items-end">
                                <div class="sm:col-span-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Material</label>
                                    <select :name="`materials[${idx}][material_id]`"
                                            x-model="m.id"
                                            required
                                            class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                        <option value="">— Choose material —</option>
                                        @foreach($rawMaterialsList as $mat)
                                        <option value="{{ $mat->id }}">
                                            {{ $mat->material_name }}
                                            ({{ number_format($mat->stock_quantity, 2) }} {{ $mat->unit }} available)
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Quantity Used</label>
                                    <input type="number"
                                           :name="`materials[${idx}][quantity]`"
                                           x-model="m.qty"
                                           min="0.01" step="0.01" required
                                           placeholder="e.g. 2.5"
                                           class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                </div>
                                <div class="sm:col-span-1 flex items-end justify-end sm:justify-center">
                                    <button type="button"
                                            @click="usedMaterials.splice(idx, 1)"
                                            class="w-10 h-11 flex items-center justify-center rounded-xl bg-red-50 hover:bg-red-100 text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                @error('materials')
                <div class="px-5 py-3 bg-red-50 border-t border-red-200">
                    <p class="text-xs text-red-600">{{ $message }}</p>
                </div>
                @enderror
            </div>

            {{-- Submit button --}}
            <button type="submit"
                    class="w-full h-14 bg-navy hover:bg-navy-dark text-white font-bold text-base rounded-2xl
                           transition shadow-md flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Submit Production Log
            </button>
            <p class="text-center text-xs text-gray-400 mt-2">
                ⚠ This entry cannot be changed after submission.
                Stock will update immediately.
            </p>
        </form>

        {{-- ===== Confirmation modal ===== --}}
        <div x-show="showConfirm" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="showConfirm = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <div class="text-center mb-5">
                    <div class="w-16 h-16 bg-navy/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-ink">Confirm Submission</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        You are about to log
                        <strong x-text="qtyProduced + ' item(s) produced'"></strong>.
                        This cannot be undone.
                    </p>
                </div>

                <div class="bg-sand rounded-xl px-4 py-3 mb-5 text-sm text-center text-gray-700">
                    Stock will be updated immediately after submission.
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="showConfirm = false"
                            class="flex-1 h-11 bg-gray-100 hover:bg-gray-200 text-ink font-medium text-sm rounded-xl transition">
                        Go Back
                    </button>
                    <button type="button"
                            :disabled="submitting"
                            @click="submitting = true; showConfirm = false; $nextTick(() => document.getElementById('prod-form').submit())"
                            class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        <span x-text="submitting ? 'Submitting…' : 'Yes, Submit'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
    @endif

</x-layouts.app>
