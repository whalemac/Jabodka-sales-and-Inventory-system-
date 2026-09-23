<x-layouts.app title="Add Stock — Import Delivery">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Import Delivery (Stock In)</h1>
        <p class="text-sm text-gray-500 mt-0.5">Record a supplier delivery and restock variants.</p>
    </div>

    <div class="max-w-2xl" x-data="importForm()">
        <form id="import-form" method="POST" action="{{ route('admin.stock.import') }}">
            @csrf

            {{-- Supplier + notes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-4 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">Supplier (optional)</label>
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
                    <label class="block text-sm font-semibold text-ink mb-1.5">Delivery Notes</label>
                    <input type="text" name="reason" value="{{ old('reason', 'Supplier delivery') }}"
                           placeholder="e.g. Weekly delivery, Invoice #1234"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
            </div>

            @if($errors->has('items'))
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                {{ $errors->first('items') }}
            </div>
            @endif

            {{-- Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Items Received</h2>
                    <button type="button" @click="addRow()"
                            class="inline-flex items-center gap-1.5 text-sm text-navy font-medium hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add row
                    </button>
                </div>

                <div class="divide-y divide-gray-50">
                    <template x-for="(row, idx) in rows" :key="idx">
                        <div class="px-5 py-4">
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                                {{-- Product selector --}}
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Product / Variant</label>
                                    <select :name="`items[${idx}][variant_id]`" x-model="row.variantId" required
                                            class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                        <option value="">Select variant…</option>
                                        @foreach($products as $product)
                                        <optgroup label="{{ $product->name }} ({{ ucfirst($product->source_type) }})">
                                            @foreach($product->variants as $variant)
                                            <option value="{{ $variant->id }}">
                                                {{ $variant->label() }} — {{ $variant->stock_count }} in stock
                                            </option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Quantity --}}
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Qty Received</label>
                                    <input type="number" :name="`items[${idx}][quantity]`" x-model.number="row.quantity"
                                           min="1" required
                                           placeholder="Qty"
                                           class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                </div>

                                {{-- Remove --}}
                                <div class="sm:col-span-1 flex items-end">
                                    <button type="button" @click="removeRow(idx)"
                                            x-show="rows.length > 1"
                                            class="w-full h-11 flex items-center justify-center rounded-xl bg-red-50 hover:bg-red-100 text-red-500 transition text-sm font-medium gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 bg-sand/40">
                    <button type="button" @click="addRow()"
                            class="w-full h-10 border-2 border-dashed border-gray-300 hover:border-navy rounded-xl text-sm text-gray-400 hover:text-navy font-medium transition">
                        + Add another variant
                    </button>
                </div>
            </div>

            <div class="flex gap-3">
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

@push('scripts')
<script>
function importForm() {
    return {
        rows: [{ variantId: '', quantity: 1 }],

        addRow() {
            this.rows.push({ variantId: '', quantity: 1 });
        },

        removeRow(idx) {
            if (this.rows.length > 1) this.rows.splice(idx, 1);
        },
    };
}
</script>
@endpush

</x-layouts.app>
