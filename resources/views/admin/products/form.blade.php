<x-layouts.app title="{{ $product->exists ? 'Edit: '.$product->name : 'New Product' }}">

    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Products
        </a>
        <h1 class="text-2xl font-bold text-ink">
            {{ $product->exists ? 'Edit Product' : 'New Product' }}
        </h1>
        @if($product->exists)
        <p class="text-sm text-gray-500 mt-0.5">
            ID #{{ $product->id }}
            @if($product->supplier) · Supplier: <span class="font-medium text-ink">{{ $product->supplier->supplier_name }}</span> @endif
        </p>
        @endif
    </div>

    @php
        $existingVariants = $product->exists
            ? $product->variants->map(fn ($v) => [
                'id'            => $v->id,
                'size'          => $v->size ?? '',
                'version'       => $v->version ?? '',
                'stock_count'   => $v->stock_count,
                'reorder_level' => $v->reorder_level,
                'has_sales'     => $v->salesItems()->exists(),
              ])->values()->toArray()
            : [['id' => null, 'size' => '', 'version' => '', 'stock_count' => 0, 'reorder_level' => 0, 'has_sales' => false]];
    @endphp

    <div class="max-w-2xl"
         x-data="{
            sourceType: '{{ old('source_type', $product->source_type ?? 'sourced') }}',
            variants: {{ json_encode(old('variants') ? collect(old('variants'))->map(fn($v, $i) => array_merge($existingVariants[$i] ?? ['id'=>null,'has_sales'=>false], $v))->values()->all() : $existingVariants) }},
            deleteIds: [],

            addVariant() {
                this.variants.push({ id: null, size: '', version: '', stock_count: 0, reorder_level: 0, has_sales: false });
            },

            removeVariant(idx) {
                const v = this.variants[idx];
                if (v.id) {
                    if (v.has_sales) {
                        alert('This variant has sales history and cannot be deleted. You can change its reorder level instead.');
                        return;
                    }
                    if (!confirm('Delete this variant? This cannot be undone.')) return;
                    this.deleteIds.push(String(v.id));
                }
                this.variants.splice(idx, 1);
                if (this.variants.length === 0) this.addVariant();
            },
         }">

        <form method="POST"
              action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if($product->exists) @method('PUT') @endif

            {{-- Hidden field: variant IDs to delete --}}
            <input type="hidden" name="delete_variant_ids" :value="deleteIds.join(',')">

            {{-- ===== Product Details ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5 space-y-4">
                <h2 class="font-semibold text-ink">Product Details</h2>

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input name="name" type="text" required
                           value="{{ old('name', $product->name) }}"
                           placeholder="e.g. Paracord Bracelet"
                           class="w-full h-11 px-4 rounded-xl border-2 text-sm focus:outline-none focus:border-navy
                                  {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Category --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Category</label>
                        <input name="category" type="text"
                               value="{{ old('category', $product->category) }}"
                               placeholder="e.g. Bags, Accessories, Clothing"
                               list="category-suggestions"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                        <datalist id="category-suggestions">
                            @foreach(['Accessories','Bags','Clothing','Equipment','Lighting'] as $cat)
                            <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                    </div>

                    {{-- Base price --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            Base / Selling Price (₱) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-400 text-sm font-medium">₱</span>
                            <input name="base_price" type="number" step="0.01" min="0" required
                                   value="{{ old('base_price', $product->base_price) }}"
                                   placeholder="0.00"
                                   class="w-full h-11 pl-8 pr-4 rounded-xl border-2 text-sm focus:outline-none focus:border-navy
                                          {{ $errors->has('base_price') ? 'border-red-400' : 'border-gray-200' }}">
                        </div>
                        @error('base_price')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Source type --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            Source Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach([
                                ['value' => 'handmade',    'label' => 'Handmade',    'color' => 'blue'],
                                ['value' => 'sourced',     'label' => 'Sourced',     'color' => 'purple'],
                                ['value' => 'consignment', 'label' => 'Consignment', 'color' => 'orange'],
                            ] as $opt)
                            <label @click="sourceType = '{{ $opt['value'] }}'" class="cursor-pointer">
                                <input type="radio" name="source_type" value="{{ $opt['value'] }}"
                                       x-model="sourceType" class="sr-only">
                                <div :class="sourceType === '{{ $opt['value'] }}'
                                         ? 'border-{{ $opt['color'] }}-400 bg-{{ $opt['color'] }}-50 text-{{ $opt['color'] }}-700'
                                         : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                     class="h-11 rounded-xl border-2 flex items-center justify-center text-xs font-semibold transition text-center px-1">
                                    {{ $opt['label'] }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('source_type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Supplier (hidden for handmade) --}}
                    <div x-show="sourceType !== 'handmade'" x-cloak>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Supplier</label>
                        <select name="supplier_id"
                                class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">— No supplier assigned —</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}"
                                {{ old('supplier_id', $product->supplier_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->supplier_name }}
                            </option>
                            @endforeach
                        </select>
                        @if(!$suppliers->count())
                        <p class="text-xs text-gray-400 mt-1">
                            No suppliers yet.
                            <a href="{{ route('admin.suppliers.create') }}" class="text-navy hover:underline" target="_blank">Add one →</a>
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Variants ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="font-semibold text-ink">Variants</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Each variant is a unique size/version combination. Set reorder level to get low-stock alerts.
                        </p>
                    </div>
                    <button type="button" @click="addVariant()"
                            class="inline-flex items-center gap-1.5 text-xs text-navy font-semibold hover:underline shrink-0 ml-4">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Variant
                    </button>
                </div>

                {{-- Column headers --}}
                <div class="hidden sm:grid sm:grid-cols-12 gap-3 px-5 py-2.5 bg-sand border-b border-gray-100
                            text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <div class="col-span-3">Size</div>
                    <div class="col-span-3">Version / Color</div>
                    <div class="col-span-2 text-center">
                        @if($product->exists) Current Stock @else Opening Stock @endif
                    </div>
                    <div class="col-span-2 text-center">Reorder At</div>
                    <div class="col-span-2"></div>
                </div>

                <div class="divide-y divide-gray-50">
                    <template x-for="(v, idx) in variants" :key="idx">
                        <div class="px-5 py-4"
                             :class="v.has_sales ? 'bg-gray-50/50' : ''">

                            {{-- Mobile label for existing with sales --}}
                            <div x-show="v.has_sales" x-cloak class="mb-2">
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Has sales — name cannot change
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">

                                {{-- Size --}}
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Size</label>
                                    <input type="hidden" :name="`variants[${idx}][id]`" :value="v.id">
                                    <input type="text"
                                           :name="`variants[${idx}][size]`"
                                           x-model="v.size"
                                           :readonly="v.has_sales"
                                           placeholder="e.g. S, M, L, 5L"
                                           :class="v.has_sales ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white'"
                                           class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                                </div>

                                {{-- Version --}}
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Version / Color</label>
                                    <input type="text"
                                           :name="`variants[${idx}][version]`"
                                           x-model="v.version"
                                           :readonly="v.has_sales"
                                           placeholder="e.g. Black, Olive"
                                           :class="v.has_sales ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white'"
                                           class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                                </div>

                                {{-- Stock count --}}
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">
                                        @if($product->exists) Current Stock @else Opening Stock @endif
                                    </label>
                                    <input type="number"
                                           :name="`variants[${idx}][stock_count]`"
                                           x-model.number="v.stock_count"
                                           min="0"
                                           :readonly="v.id !== null"
                                           :class="v.id !== null ? 'bg-gray-50 text-gray-500 cursor-default font-semibold' : 'bg-white'"
                                           class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 text-sm text-center focus:outline-none focus:border-navy">
                                    <p x-show="v.id !== null" class="text-xs text-gray-400 mt-0.5 text-center hidden sm:block">
                                        Use Stock Import to change
                                    </p>
                                </div>

                                {{-- Reorder level --}}
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Reorder At</label>
                                    <input type="number"
                                           :name="`variants[${idx}][reorder_level]`"
                                           x-model.number="v.reorder_level"
                                           min="0"
                                           placeholder="0"
                                           class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm text-center focus:outline-none focus:border-navy">
                                </div>

                                {{-- Delete button --}}
                                <div class="sm:col-span-2 flex items-center justify-end sm:justify-center gap-2">
                                    <a x-show="v.id !== null" x-cloak
                                       :href="`/admin/stock/adjust/${v.id}`"
                                       class="h-8 px-2.5 inline-flex items-center text-xs text-navy font-medium rounded-lg bg-navy/10 hover:bg-navy hover:text-white transition"
                                       title="Adjust stock">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                            @click="removeVariant(idx)"
                                            x-show="variants.length > 1 || v.id === null"
                                            class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                            </div>

                            {{-- Mobile: stock note for existing variants --}}
                            <div x-show="v.id !== null" x-cloak class="mt-2 sm:hidden">
                                <p class="text-xs text-gray-400">
                                    Stock count is managed via Stock Management → Adjust or Import.
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Add variant button --}}
                <div class="px-5 py-3 bg-sand/40 border-t border-gray-100">
                    <button type="button" @click="addVariant()"
                            class="w-full h-9 border-2 border-dashed border-gray-300 hover:border-navy rounded-xl text-sm text-gray-400 hover:text-navy font-medium transition">
                        + Add another variant
                    </button>
                </div>
            </div>

            {{-- Stock count note --}}
            @if($product->exists)
            <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 mb-5">
                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-blue-800">
                    <strong>Stock counts are read-only here.</strong>
                    Use <a href="{{ route('admin.stock.import-form') }}" class="font-semibold underline">Import Delivery</a>
                    to add stock from a supplier, or
                    <a href="{{ route('admin.stock.index') }}" class="font-semibold underline">Stock Management → Adjust</a>
                    for corrections and damage.
                </p>
            </div>
            @endif

            {{-- Form actions --}}
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 h-12 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition shadow-sm">
                    {{ $product->exists ? 'Save Changes' : 'Create Product' }}
                </button>
                <a href="{{ route('admin.products.index') }}"
                   class="flex-1 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                    Cancel
                </a>
            </div>

            {{-- Danger zone: delete product --}}
            @if($product->exists)
            <div class="mt-8 border border-red-200 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-red-700 mb-1">Danger Zone</h3>
                <p class="text-xs text-gray-500 mb-3">
                    Deleting a product removes it and all its variants permanently.
                    Products with sales history cannot be deleted.
                </p>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                      onsubmit="return confirm('Delete {{ addslashes($product->name) }} and all its variants? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="h-9 px-4 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold rounded-xl transition border border-red-200">
                        Delete This Product
                    </button>
                </form>
                @error('delete')
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>
            @endif

        </form>
    </div>

</x-layouts.app>
