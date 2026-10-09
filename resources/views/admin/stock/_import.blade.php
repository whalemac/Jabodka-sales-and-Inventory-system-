    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
    @endif

    @php
        $variantList = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $variantList[] = [
                    'id' => $variant->id,
                    'label' => $product->name.' — '.($variant->label() ?: 'Standard'),
                    'stock' => $variant->stock_count,
                    'type' => $product->source_type,
                ];
            }
        }
    @endphp

    <div class="max-w-3xl" x-data="invoiceCart({{ json_encode($variantList) }}, {{ $preselect ?? 'null' }})">
        <form method="POST" action="{{ route('admin.stock.import') }}" class="space-y-4">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Invoice header</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Supplier <span class="text-red-500">*</span></label>
                        <select name="supplier_id"
                                data-validate="required" data-field="Supplier"
                                class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">Select supplier…</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->supplier_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Invoice # <span class="text-gray-400 font-normal">(auto)</span></label>
                        <input type="text" name="invoice_number" readonly value="{{ old('invoice_number', $nextInvoiceNumber) }}"
                               placeholder="{{ $nextInvoiceNumber }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-500 text-sm focus:outline-none cursor-not-allowed">
                        <p class="text-xs text-gray-400 mt-1">Auto-generated, sequential per day (INV-YYYYMMDD-0001).</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Received date <span class="text-red-500">*</span></label>
                        <input type="date" name="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}"
                               data-validate="required" data-field="Received date"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Add line items</h2>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5 relative">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search variant</label>
                        <input type="text" x-model="query" @input="filter()" placeholder="Type a product name…"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                        <div x-show="matches.length" x-cloak class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-auto">
                            <template x-for="m in matches" :key="m.id">
                                <button type="button" @click="pick(m)" class="w-full text-left px-3 py-2 text-sm hover:bg-sand">
                                    <span x-text="m.label"></span>
                                    <span class="text-xs text-gray-400" x-text="' · ' + m.stock + ' in stock'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Qty</label>
                        <input type="text" inputmode="numeric" x-model.number="draft.qty" data-validate="integer" data-numeric="integer" class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm text-center no-spinner">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Unit cost (₱)</label>
                        <input type="text" inputmode="decimal" x-model="draft.cost" placeholder="Optional"
                               data-validate="money" data-numeric="money"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm no-spinner">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="button" @click="addLine()"
                                class="w-full h-11 bg-navy text-white text-sm font-semibold rounded-xl hover:bg-navy-dark">Add line</button>
                    </div>
                </div>
                <p class="text-xs text-gray-400" x-show="draft.label" x-cloak>
                    Selected: <span class="font-medium text-ink" x-text="draft.label"></span>
                </p>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <template x-if="lines.length === 0">
                        <p class="px-4 py-8 text-center text-sm text-gray-400">No lines yet. Search and add products received on this invoice.</p>
                    </template>
                    <template x-if="lines.length > 0">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-sand border-b-2 border-gray-200 text-left text-xs text-gray-500">
                                    <th class="px-4 py-2 font-semibold">Variant</th>
                                    <th class="px-4 py-2 font-semibold text-center">Qty</th>
                                    <th class="px-4 py-2 font-semibold text-right">Unit cost</th>
                                    <th class="px-4 py-2 font-semibold text-right">Line total</th>
                                    <th class="px-4 py-2 font-semibold text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, idx) in lines" :key="idx">
                                    <tr class="border-t border-gray-50">
                                        <td class="px-4 py-2">
                                            <input type="hidden" :name="`items[${idx}][variant_id]`" :value="line.variant_id">
                                            <span x-text="line.label"></span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="text" inputmode="numeric" :name="`items[${idx}][quantity_received]`" x-model.number="line.qty" data-validate="integer" data-numeric="integer" class="w-16 h-9 text-center border-2 border-gray-200 rounded-lg no-spinner">
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <input type="text" inputmode="decimal" :name="`items[${idx}][unit_cost]`" x-model="line.cost" data-validate="money" data-numeric="money" class="w-24 h-9 text-right border-2 border-gray-200 rounded-lg ml-auto no-spinner">
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium" x-text="lineTotal(line)"></td>
                                        <td class="px-2 text-center">
                                            <button type="button" @click="lines.splice(idx, 1)" class="text-red-500 text-xs font-semibold">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>
                <p class="text-sm text-gray-600">
                    <span class="font-semibold text-ink" x-text="lines.length"></span> line(s) ·
                    Total ₱<span class="font-semibold text-ink" x-text="grandTotal()"></span>
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" :disabled="lines.length === 0"
                        class="flex-1 h-12 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition disabled:opacity-50">
                    Save invoice
                </button>
                <a href="{{ route('admin.stock.index') }}"
                   class="flex-1 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl">
                    Cancel
                </a>
            </div>
        </form>

    </div>
