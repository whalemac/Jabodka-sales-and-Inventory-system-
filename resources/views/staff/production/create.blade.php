<x-layouts.app title="Log Production">
    <div class="mb-6">
        <a href="{{ route('staff.production.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Production Log
        </a>
        <h1 class="text-2xl font-bold text-ink">Log Production</h1>
        <p class="text-sm text-gray-500 mt-0.5">This entry cannot be edited once submitted.</p>
    </div>

    <div class="max-w-2xl space-y-5" x-data="{ materials: [] }">
        <form method="POST" action="{{ route('staff.production.store') }}">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Production Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Handmade Product / Variant <span class="text-red-500">*</span></label>
                        <select name="variant_id" required class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('variant_id') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">Select product variant…</option>
                            @foreach($variants as $variant)
                            <option value="{{ $variant->id }}" {{ old('variant_id') == $variant->id ? 'selected' : '' }}>
                                {{ $variant->displayName() }} ({{ $variant->stock_count }} in stock)
                            </option>
                            @endforeach
                        </select>
                        @error('variant_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Quantity Produced <span class="text-red-500">*</span></label>
                        <input name="quantity_produced" type="number" min="1" required value="{{ old('quantity_produced', 1) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('quantity_produced') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Production Date <span class="text-red-500">*</span></label>
                        <input name="production_date" type="date" required value="{{ old('production_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}"
                               class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('production_date') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                        <textarea name="notes" rows="2" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-sm resize-none focus:outline-none focus:border-navy">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Materials used --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-ink text-sm">Raw Materials Used</h2>
                    <button type="button" @click="materials.push({id:'',qty:''})" class="text-xs text-navy font-medium hover:underline">+ Add material</button>
                </div>
                @error('materials')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
                <div x-show="materials.length === 0" class="text-sm text-gray-400 text-center py-4">
                    No materials added. Click "+ Add material" if this production uses raw materials.
                </div>
                <div class="space-y-3">
                    <template x-for="(m, idx) in materials" :key="idx">
                        <div class="grid grid-cols-5 gap-2 items-end">
                            <div class="col-span-3">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Material</label>
                                <select :name="`materials[${idx}][material_id]`" x-model="m.id" required
                                        class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                    <option value="">Select material…</option>
                                    @foreach($materials as $mat)
                                    <option value="{{ $mat->id }}">{{ $mat->material_name }} ({{ number_format($mat->stock_quantity, 2) }} {{ $mat->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Quantity</label>
                                <input type="number" :name="`materials[${idx}][quantity]`" x-model="m.qty" min="0.01" step="0.01" required placeholder="Qty"
                                       class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                            <button type="button" @click="materials.splice(idx, 1)"
                                    class="h-10 flex items-center justify-center text-red-400 hover:text-red-600 text-xl font-bold">×</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 h-12 bg-navy hover:bg-navy-dark text-white font-bold text-sm rounded-xl transition shadow-sm">Submit Production Log</button>
                <a href="{{ route('staff.production.index') }}" class="flex-1 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</a>
            </div>
            <p class="text-center text-xs text-gray-400 mt-2">Stock will be incremented immediately. This entry cannot be edited after submission.</p>
        </form>
    </div>
</x-layouts.app>
