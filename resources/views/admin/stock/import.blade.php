<x-layouts.app title="Add Stock — Import Delivery">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Add Stock — Import Delivery</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Record a supplier delivery. Each line increments the variant's stock count.
        </p>
    </div>

    @if($errors->has('items'))
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        {{ $errors->first('items') }}
    </div>
    @endif

    {{-- Build variant lookup for JS --}}
    @php
        $variantMap = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $variantMap[$variant->id] = [
                    'label'  => $product->name . ' — ' . ($variant->label() ?: 'Standard'),
                    'stock'  => $variant->stock_count,
                    'low'    => $variant->isLowStock(),
                    'out'    => $variant->stock_count === 0,
                ];
            }
        }
    @endphp

    <div class="max-w-2xl space-y-4"
         x-data="importForm({{ json_encode($variantMap) }}, {{ $preselect ?? 'null' }})">

        {{-- Delivery info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-ink text-sm">Delivery Information</h2>

            <form id="import-form" method="POST" action="{{ route('admin.stock.import') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Supplier</label>
                        <select name="supplier_id"
                                class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">— No specific supplier —</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->supplier_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Delivery Notes / Reference</label>
                        <input type="text" name="reason"
                               value="{{ old('reason', 'Supplier delivery') }}"
                               placeholder="e.g. Weekly delivery, Invoice #1234"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>

                {{-- Items received --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 bg-sand border-b border-gray-200">
                        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Items Received</h3>
                        <button type="button" @click="addRow()"
                                class="inline-flex items-center gap-1 text-xs text-navy font-semibold hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add row
                        </button>
                    </div>

                    {{-- Column headers (desktop) --}}
                    <div class="hidden sm:grid sm:grid-cols-12 gap-3 px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <div class="col-span-7">Product / Variant</div>
                        <div class="col-span-2 text-center">Current Stock</div>
                        <div class="col-span-2 text-center">Qty to Add</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <template x-for="(row, idx) in rows" :key="idx">
                            <div class="px-4 py-3" :class="row.out ? 'bg-red-50/30' : (row.low ? 'bg-yellow-50/30' : '')">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">

                                    {{-- Variant selector --}}
                                    <div class="sm:col-span-7">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Product / Variant</label>
                                        <select :name="`items[${idx}][variant_id]`"
                                                x-model="row.variantId"
                                                @change="onVariantChange(idx, $event.target.value)"
                                                required
                                                class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                            <option value="">Select variant…</option>
                                            @foreach($products as $product)
                                            <optgroup label="{{ $product->name }} ({{ ucfirst($product->source_type) }})">
                                                @foreach($product->variants as $variant)
                                                <option value="{{ $variant->id }}">
                                                    {{ $variant->label() ?: 'Standard' }}
                                                    ({{ $variant->stock_count }} in stock{{ $variant->isLowStock() ? ' ⚠' : '' }})
                                                </option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                        </select>

                                        {{-- Low/out badges below selector --}}
                                        <div class="mt-1 flex gap-1" x-show="row.variantId">
                                            <span x-show="row.out" x-cloak
                                                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                                Out of stock — needs restocking
                                            </span>
                                            <span x-show="row.low && !row.out" x-cloak
                                                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                                                ⚠ Low stock
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Current stock (read-only display) --}}
                                    <div class="sm:col-span-2 text-center">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Current Stock</label>
                                        <span class="text-base font-bold"
                                              :class="row.out ? 'text-red-600' : (row.low ? 'text-yellow-600' : 'text-gray-700')"
                                              x-text="row.variantId ? row.currentStock : '—'">
                                        </span>
                                    </div>

                                    {{-- Qty to add --}}
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Qty to Add</label>
                                        <input type="number"
                                               :name="`items[${idx}][quantity]`"
                                               x-model.number="row.quantity"
                                               min="1" required
                                               placeholder="Qty"
                                               class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm text-center font-semibold focus:outline-none focus:border-navy">
                                    </div>

                                    {{-- Remove row --}}
                                    <div class="sm:col-span-1 flex justify-end sm:justify-center">
                                        <button type="button"
                                                @click="removeRow(idx)"
                                                x-show="rows.length > 1"
                                                class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-500 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                        <button type="button" @click="addRow()"
                                class="w-full h-9 border-2 border-dashed border-gray-300 hover:border-navy rounded-xl text-sm text-gray-400 hover:text-navy font-medium transition">
                            + Add another variant
                        </button>
                    </div>
                </div>

                {{-- Summary --}}
                <div x-show="rows.filter(r => r.variantId && r.quantity > 0).length > 0" x-cloak
                     class="mt-4 bg-sand rounded-xl px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        <span class="font-semibold text-ink" x-text="rows.filter(r => r.variantId && r.quantity > 0).length"></span>
                        variant(s) ·
                        <span class="font-semibold text-ink" x-text="rows.reduce((s,r) => s + (parseInt(r.quantity)||0), 0)"></span>
                        units total
                    </span>
                    <span class="text-xs text-gray-400">All counts will increment immediately on submit.</span>
                </div>

                <div class="flex gap-3 mt-4">
                    <button type="submit"
                            class="flex-1 h-12 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition shadow-sm">
                        Record Delivery
                    </button>
                    <a href="{{ route('admin.stock.index') }}"
                       class="flex-1 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

@push('scripts')
<script>
function importForm(variantMap, preselect) {
    return {
        rows: [{ variantId: preselect ? String(preselect) : '', quantity: 1, currentStock: 0, low: false, out: false }],

        init() {
            // If pre-selected from stock index, update the display fields
            if (preselect) {
                this.onVariantChange(0, String(preselect));
            }
        },

        onVariantChange(idx, variantId) {
            const info = variantMap[variantId] || null;
            this.rows[idx].variantId    = variantId;
            this.rows[idx].currentStock = info ? info.stock : 0;
            this.rows[idx].low          = info ? info.low : false;
            this.rows[idx].out          = info ? info.out : false;
        },

        addRow() {
            this.rows.push({ variantId: '', quantity: 1, currentStock: 0, low: false, out: false });
        },

        removeRow(idx) {
            if (this.rows.length > 1) this.rows.splice(idx, 1);
        },
    };
}
</script>
@endpush

</x-layouts.app>
