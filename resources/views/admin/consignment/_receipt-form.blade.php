    @php
        $variantList = $variants->map(fn ($v) => [
            'id' => $v->id,
            'label' => $v->displayName(),
            'stock' => $v->stock_count,
        ])->values();
    @endphp

    <div class="max-w-3xl" x-data="consignmentCart({{ json_encode($variantList) }})">
        <form method="POST" action="{{ route('admin.consignment.items.store') }}" class="space-y-4">
            @csrf
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Drop-off header</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Partner <span class="text-red-500">*</span></label>
                        <select name="partner_id" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">Select partner…</option>
                            @foreach($partners as $p)
                            <option value="{{ $p->id }}" {{ old('partner_id', $prePartner) == $p->id ? 'selected' : '' }}>{{ $p->partner_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Received date <span class="text-red-500">*</span></label>
                        <input type="date" name="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}"
                               data-validate="required|date" data-field="Received date"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Add items</h2>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-4 relative">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search variant</label>
                        <input type="text" x-model="query" @input="filter()" placeholder="Type a product…"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                        <div x-show="matches.length" x-cloak class="absolute z-20 mt-1 w-full bg-white border rounded-xl shadow-lg max-h-40 overflow-auto">
                            <template x-for="m in matches" :key="m.id">
                                <button type="button" @click="pick(m)" class="w-full text-left px-3 py-2 text-sm hover:bg-sand" x-text="m.label"></button>
                            </template>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Units</label>
                        <input type="text" inputmode="numeric" x-model.number="draft.qty"
                               data-numeric="integer" data-label="Units" data-validate="integer|positive" data-field="Units"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm text-center no-spinner">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Base ₱</label>
                        <input type="text" inputmode="decimal" x-model="draft.base"
                               data-numeric="money" data-label="Base ₱" data-validate="money" data-field="Base ₱"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm no-spinner">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Markup ₱</label>
                        <input type="text" inputmode="decimal" x-model="draft.markup"
                               data-numeric="money" data-label="Markup ₱" data-validate="money" data-field="Markup ₱"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm no-spinner">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="button" @click="addLine()" class="w-full h-11 bg-navy text-white text-sm font-semibold rounded-xl">Add line</button>
                    </div>
                </div>
                <p class="text-xs text-gray-400" x-show="draft.label" x-cloak>Selected: <span class="font-medium text-ink" x-text="draft.label"></span></p>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <template x-if="lines.length === 0">
                        <p class="px-4 py-8 text-center text-sm text-gray-400">No lines yet.</p>
                    </template>
                    <template x-if="lines.length > 0">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-sand border-b-2 border-gray-200 text-xs text-gray-500 text-left">
                                    <th class="px-3 py-2">Variant</th>
                                    <th class="px-3 py-2 text-center">Units</th>
                                    <th class="px-3 py-2 text-right">Base</th>
                                    <th class="px-3 py-2 text-right">Markup</th>
                                    <th class="px-3 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, idx) in lines" :key="idx">
                                    <tr class="border-t border-gray-50">
                                        <td class="px-3 py-2">
                                            <input type="hidden" :name="`items[${idx}][variant_id]`" :value="line.variant_id">
                                            <span x-text="line.label"></span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="text" inputmode="numeric" :name="`items[${idx}][units_delivered]`" x-model.number="line.qty"
                                                   data-numeric="integer" data-label="Units" data-validate="required|integer|positive" data-field="Units"
                                                   class="w-16 h-9 text-center border-2 rounded-lg no-spinner">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="text" inputmode="decimal" :name="`items[${idx}][agreed_base_price]`" x-model="line.base"
                                                   data-numeric="money" data-label="Base price" data-validate="required|money" data-field="Base ₱"
                                                   class="w-24 h-9 text-right border-2 rounded-lg no-spinner">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="text" inputmode="decimal" :name="`items[${idx}][shop_markup]`" x-model="line.markup"
                                                   data-numeric="money" data-label="Markup" data-validate="required|money" data-field="Markup ₱"
                                                   class="w-24 h-9 text-right border-2 rounded-lg no-spinner">
                                        </td>
                                        <td class="px-3 py-2 text-center"><button type="button" @click="lines.splice(idx,1)" class="text-xs text-red-500 font-semibold">Remove</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" :disabled="lines.length === 0" class="flex-1 h-12 bg-navy text-white font-semibold rounded-xl disabled:opacity-50">Save drop-off</button>
                <a href="{{ route('admin.consignment.index') }}" class="flex-1 h-12 flex items-center justify-center bg-gray-100 rounded-xl text-sm font-medium">Cancel</a>
            </div>
        </form>
    </div>
