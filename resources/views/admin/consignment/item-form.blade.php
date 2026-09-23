<x-layouts.app title="{{ $item->exists ? 'Edit Consignment Item' : 'Add Consignment Item' }}">

    <div class="mb-6">
        <a href="{{ route('admin.consignment.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Consignment
        </a>
        <h1 class="text-2xl font-bold text-ink">
            {{ $item->exists ? 'Edit Consignment Item' : 'Add Consignment Item' }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Record items delivered by a partner on consignment arrangement.
        </p>
    </div>

    <div class="max-w-lg"
         x-data="{
            basePrice: {{ old('agreed_base_price', $item->agreed_base_price ?? 0) }},
            markup:    {{ old('shop_markup',       $item->shop_markup ?? 0) }},
            get sellingPrice() { return (parseFloat(this.basePrice) || 0) + (parseFloat(this.markup) || 0); }
         }">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST"
                  action="{{ $item->exists
                      ? route('admin.consignment.items.update', $item)
                      : route('admin.consignment.items.store') }}"
                  class="space-y-4">
                @csrf
                @if($item->exists) @method('PUT') @endif

                {{-- Partner --}}
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Partner <span class="text-red-500">*</span>
                    </label>
                    <select name="partner_id" required
                            class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('partner_id') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                        <option value="">Select partner…</option>
                        @foreach($partners as $p)
                        <option value="{{ $p->id }}"
                            {{ old('partner_id', $item->partner_id ?? $prePartner) == $p->id ? 'selected' : '' }}>
                            {{ $p->partner_name }}
                        </option>
                        @endforeach
                    </select>
                    @error('partner_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @if(!$partners->count())
                    <p class="text-xs text-gray-400 mt-1">
                        No partners yet.
                        <a href="{{ route('admin.consignment.partners.create') }}" class="text-navy hover:underline" target="_blank">Add one →</a>
                    </p>
                    @endif
                </div>

                {{-- Product Variant --}}
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Product / Variant <span class="text-red-500">*</span>
                    </label>
                    <select name="variant_id" required
                            class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('variant_id') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                        <option value="">Select variant…</option>
                        @foreach($variants->groupBy('product.name') as $productName => $group)
                        <optgroup label="{{ $productName }}">
                            @foreach($group as $v)
                            <option value="{{ $v->id }}"
                                {{ old('variant_id', $item->variant_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->label() ?: 'Standard' }}
                                ({{ $v->stock_count }} in stock)
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('variant_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @if(!$variants->count())
                    <p class="text-xs text-gray-400 mt-1">
                        No consignment-type products exist yet.
                        <a href="{{ route('admin.products.create') }}" class="text-navy hover:underline" target="_blank">Add a product →</a>
                    </p>
                    @endif
                </div>

                {{-- Units + Date --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">
                            Units Delivered <span class="text-red-500">*</span>
                        </label>
                        <input name="units_delivered" type="number" min="1" required
                               value="{{ old('units_delivered', $item->units_delivered) }}"
                               placeholder="e.g. 10"
                               class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('units_delivered') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                        @error('units_delivered')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Received At</label>
                        <input name="received_at" type="date" required
                               value="{{ old('received_at', $item->exists ? $item->received_at->format('Y-m-d') : now()->format('Y-m-d')) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="bg-sand px-4 py-2.5 border-b border-gray-200">
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Pricing Agreement</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                    Partner Base Price (₱) <span class="text-red-500">*</span>
                                    <span class="text-gray-400 font-normal block">Amount owed to partner per unit sold</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-400 text-sm">₱</span>
                                    <input name="agreed_base_price" type="number" step="0.01" min="0" required
                                           x-model.number="basePrice"
                                           value="{{ old('agreed_base_price', $item->agreed_base_price) }}"
                                           placeholder="0.00"
                                           class="w-full h-11 pl-7 pr-4 rounded-xl border-2 {{ $errors->has('agreed_base_price') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                                </div>
                                @error('agreed_base_price')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                    Shop Markup (₱) <span class="text-red-500">*</span>
                                    <span class="text-gray-400 font-normal block">Added on top for the shop's margin</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-400 text-sm">₱</span>
                                    <input name="shop_markup" type="number" step="0.01" min="0" required
                                           x-model.number="markup"
                                           value="{{ old('shop_markup', $item->shop_markup ?? 0) }}"
                                           placeholder="0.00"
                                           class="w-full h-11 pl-7 pr-4 rounded-xl border-2 {{ $errors->has('shop_markup') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                                </div>
                                @error('shop_markup')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Live price preview --}}
                        <div class="bg-navy/5 border border-navy/20 rounded-xl px-4 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Selling price per unit</p>
                                <p class="text-xs text-gray-400 mt-0.5">base (₱<span x-text="parseFloat(basePrice||0).toFixed(2)"></span>) + markup (₱<span x-text="parseFloat(markup||0).toFixed(2)"></span>)</p>
                            </div>
                            <p class="text-xl font-bold text-navy" x-text="'₱' + sellingPrice.toFixed(2)">
                                ₱{{ number_format(($item->agreed_base_price ?? 0) + ($item->shop_markup ?? 0), 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="submit"
                            class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        {{ $item->exists ? 'Save Changes' : 'Record Item' }}
                    </button>
                    <a href="{{ route('admin.consignment.index') }}"
                       class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
