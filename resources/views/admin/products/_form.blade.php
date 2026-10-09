    @php
        $existingVariants = $product->exists
            ? $product->variants->map(fn ($v) => [
                'id'            => $v->id,
                'size'          => $v->size ?? '',
                'version'       => $v->version ?? '',
                'stock_count'   => $v->stock_count,
                'reorder_level' => $v->reorder_level,
                'is_active'     => (bool) $v->is_active,
                'has_sales'     => $v->salesItems()->exists(),
              ])->values()->toArray()
            : [['id' => null, 'size' => '', 'version' => '', 'stock_count' => 0, 'reorder_level' => 0, 'is_active' => true, 'has_sales' => false]];
    @endphp

    <div class="max-w-2xl"
         x-data="{
            sourceType: '{{ old('source_type', $product->source_type ?? 'sourced') }}',
            variants: {{ json_encode(old('variants') ? collect(old('variants'))->map(fn($v, $i) => array_merge($existingVariants[$i] ?? ['id'=>null,'has_sales'=>false,'is_active'=>true], $v))->values()->all() : $existingVariants) }},

            addVariant() {
                this.variants.push({ id: null, size: '', version: '', stock_count: 0, reorder_level: 0, is_active: true, has_sales: false });
            },

            removeVariant(idx) {
                const v = this.variants[idx];
                if (v.id) {
                    v.is_active = !v.is_active;
                    return;
                }
                this.variants.splice(idx, 1);
                if (this.variants.length === 0) this.addVariant();
            },
         }">

        <form method="POST"
              action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if($product->exists) @method('PUT') @endif

            {{-- ===== Product Details ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5 space-y-4">
                <h2 class="font-semibold text-ink">Product Details</h2>

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Product Name <span class="text-red-500">*</span>
                    </label>
                    <input name="name" type="text"
                           value="{{ old('name', $product->name) }}"
                           placeholder="e.g. Paracord Bracelet"
                           data-validate="required|name" data-field="Product Name"
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
                            <input name="base_price" type="text" inputmode="decimal"
                                   value="{{ old('base_price', $product->base_price) }}"
                                   placeholder="0.00"
                                   data-validate="money" data-numeric="money" data-field="Base Price"
                                   class="w-full h-11 pl-8 pr-4 rounded-xl border-2 text-sm focus:outline-none focus:border-navy no-spinner
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
                        <div class="flex gap-2">
                            <select name="supplier_id" id="supplier-select"
                                    class="flex-1 h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                <option value="">— No supplier assigned —</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    {{ old('supplier_id', $product->supplier_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->supplier_name }}
                                </option>
                                @endforeach
                            </select>
                            <button type="button"
                                    @click="$dispatch('open-supplier-modal')"
                                    class="h-11 px-3 shrink-0 inline-flex items-center gap-1 rounded-xl border-2 border-gray-200 hover:border-navy text-xs font-semibold text-navy transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add
                            </button>
                        </div>
                        @if(!$suppliers->count())
                        <p class="text-xs text-gray-400 mt-1">
                            No suppliers yet — use Add to create one without leaving this page.
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
                <div class="hidden sm:grid sm:grid-cols-12 gap-3 px-5 py-2.5 bg-sand border-b-2 border-gray-200
                            text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <div class="col-span-3">Size</div>
                    <div class="col-span-3">Version / Color</div>
                    <div class="col-span-2 text-center">
                        @if($product->exists) Current Stock @else Opening Stock @endif
                    </div>
                    <div class="col-span-2 text-center">Reorder At</div>
                    <div class="col-span-2 text-center">Active</div>
                </div>

                <div class="divide-y divide-gray-50">
                    <template x-for="(v, idx) in variants" :key="idx">
                        <div class="px-5 py-4"
                             :class="v.is_active === false ? 'bg-gray-50/80' : (v.has_sales ? 'bg-gray-50/50' : '')">

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
                                     <input type="text" inputmode="numeric"
                                            :name="`variants[${idx}][stock_count]`"
                                            x-model.number="v.stock_count"
                                            :readonly="v.id !== null"
                                            data-validate="integer" data-numeric="integer"
                                            :class="v.id !== null ? 'bg-gray-50 text-gray-500 cursor-default font-semibold' : 'bg-white'"
                                            class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 text-sm text-center focus:outline-none focus:border-navy no-spinner">
                                    <p x-show="v.id !== null" class="text-xs text-gray-400 mt-0.5 text-center hidden sm:block">
                                        Use Stock Import to change
                                    </p>
                                </div>

                                {{-- Reorder level --}}
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1 sm:hidden">Reorder At</label>
                                     <input type="text" inputmode="numeric"
                                            :name="`variants[${idx}][reorder_level]`"
                                            x-model.number="v.reorder_level"
                                            placeholder="0"
                                            data-validate="integer" data-numeric="integer"
                                            class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm text-center focus:outline-none focus:border-navy no-spinner">
                                </div>

                                {{-- Active toggle / remove unsaved --}}
                                <div class="sm:col-span-2 flex items-center justify-end sm:justify-center gap-2">
                                    <input type="hidden" :name="`variants[${idx}][is_active]`" :value="v.is_active ? 1 : 0">
                                    <a x-show="v.id !== null" x-cloak
                                       :href="`/admin/stock/adjust/${v.id}`"
                                       class="h-8 px-2.5 inline-flex items-center text-xs text-navy font-medium rounded-lg bg-navy/10 hover:bg-navy hover:text-white transition"
                                       title="Adjust stock">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button type="button" x-show="v.id !== null" x-cloak
                                            @click="v.is_active = !v.is_active"
                                            class="h-8 px-2.5 inline-flex items-center text-xs font-semibold rounded-lg transition"
                                            :class="v.is_active ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-green-50 text-green-700 hover:bg-green-100'">
                                        <span x-text="v.is_active ? 'Deactivate' : 'Activate'"></span>
                                    </button>
                                    <button type="button"
                                            @click="removeVariant(idx)"
                                            x-show="v.id === null && variants.length > 1"
                                            class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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

            @if($product->exists)
            <div class="mt-8 border border-gray-200 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-ink mb-1">Product status</h3>
                <p class="text-xs text-gray-500 mb-3">
                    Deactivated products stay in history and reports, but they no longer appear in Walk-in or Online Order search.
                </p>
                <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="h-9 px-4 text-sm font-semibold rounded-xl transition border
                                   {{ $product->is_active ? 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200' : 'bg-green-50 hover:bg-green-100 text-green-700 border-green-200' }}">
                        {{ $product->is_active ? 'Deactivate this product' : 'Activate this product' }}
                    </button>
                </form>
            </div>
            @endif

        </form>

        {{-- ===== Add supplier (inline, no page leave) ===== --}}
        <div x-data="{
                show: false,
                name: '',
                contact: '',
                saving: false,
                error: '',
                async saveSupplier() {
                    this.saving = true;
                    this.error = '';
                    try {
                        const res = await fetch('{{ route('admin.suppliers.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ supplier_name: this.name.trim(), contact_details: this.contact.trim() }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            this.error = data.message
                                || (data.errors && Object.values(data.errors).flat()[0])
                                || 'Could not save the supplier.';
                            return;
                        }
                        const select = document.getElementById('supplier-select');
                        if (select) {
                            const opt = document.createElement('option');
                            opt.value = data.id;
                            opt.textContent = data.name;
                            select.appendChild(opt);
                            select.value = String(data.id);
                        }
                        this.name = '';
                        this.contact = '';
                        this.show = false;
                    } catch (e) {
                        this.error = 'Network error — please try again.';
                    } finally {
                        this.saving = false;
                    }
                },
             }"
             @open-supplier-modal.window="show = true; error = ''"
             x-show="show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-black/40" @click="show = false"></div>
            <div x-show="show" x-transition.opacity.duration.150ms
                 class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h2 class="text-lg font-bold text-ink">Add Supplier</h2>
                <p class="text-xs text-gray-400 mt-0.5">Saved straight to the list — no need to leave this form.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Supplier name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="name"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Contact details</label>
                        <input type="text" x-model="contact" placeholder="Phone, email, address…"
                               class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>

                <p x-show="error" x-cloak class="text-xs text-red-600 mt-4" x-text="error"></p>

                <div class="flex gap-3 mt-6">
                    <button type="button" @click="saveSupplier()" :disabled="saving || !name.trim()"
                            class="flex-1 h-11 inline-flex items-center justify-center gap-2 bg-navy hover:bg-navy-dark disabled:opacity-60 text-white text-sm font-semibold rounded-xl transition">
                        <span x-show="saving" x-cloak class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        Save Supplier
                    </button>
                    <button type="button" @click="show = false"
                            class="flex-1 h-11 bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

