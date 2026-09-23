<x-layouts.app title="{{ $product->exists ? 'Edit Product' : 'New Product' }}">

    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Products
        </a>
        <h1 class="text-2xl font-bold text-ink">{{ $product->exists ? 'Edit Product' : 'New Product' }}</h1>
    </div>

    <div class="max-w-2xl space-y-5">
        <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
              x-data="{ sourceType: '{{ old('source_type', $product->source_type ?? 'sourced') }}', variants: {{ json_encode($product->exists ? $product->variants->map(fn($v) => ['id'=>$v->id,'size'=>$v->size,'version'=>$v->version,'stock_count'=>$v->stock_count,'reorder_level'=>$v->reorder_level])->values() : collect([['id'=>null,'size'=>'','version'=>'','stock_count'=>0,'reorder_level'=>0]])) }} }">
            @csrf
            @if($product->exists) @method('PUT') @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                <h2 class="font-semibold text-ink text-sm">Product Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                        <input name="name" type="text" required value="{{ old('name', $product->name) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                        <input name="category" type="text" value="{{ old('category', $product->category) }}"
                               placeholder="e.g. Bags, Tents"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Base Price (₱) <span class="text-red-500">*</span></label>
                        <input name="base_price" type="number" step="0.01" min="0" required value="{{ old('base_price', $product->base_price) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('base_price') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Source Type <span class="text-red-500">*</span></label>
                        <select name="source_type" x-model="sourceType" required
                                class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="handmade">Handmade</option>
                            <option value="sourced">Sourced (Angkat)</option>
                            <option value="consignment">Consignment</option>
                        </select>
                    </div>
                    <div x-show="sourceType !== 'handmade'">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Supplier</label>
                        <select name="supplier_id" class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">— None —</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplier_id', $product->supplier_id) == $s->id ? 'selected' : '' }}>{{ $s->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-ink text-sm">Variants (Size / Version)</h2>
                    <button type="button" @click="variants.push({id:null,size:'',version:'',stock_count:0,reorder_level:0})"
                            class="text-xs text-navy font-medium hover:underline">+ Add variant</button>
                </div>
                <div class="space-y-3">
                    <template x-for="(v, idx) in variants" :key="idx">
                        <div class="grid grid-cols-5 gap-2 items-end">
                            <input type="hidden" :name="`variants[${idx}][id]`" :value="v.id">
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Size</label>
                                <input type="text" :name="`variants[${idx}][size]`" x-model="v.size" placeholder="e.g. S, M, L"
                                       class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Version / Color</label>
                                <input type="text" :name="`variants[${idx}][version]`" x-model="v.version" placeholder="e.g. Black"
                                       class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Reorder</label>
                                <input type="number" :name="`variants[${idx}][reorder_level]`" x-model.number="v.reorder_level" min="0"
                                       class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                            <button type="button" @click="if(variants.length > 1) variants.splice(idx, 1)"
                                    x-show="variants.length > 1"
                                    class="col-span-5 sm:col-span-1 h-10 flex items-center justify-center text-red-400 hover:text-red-600 text-xs font-medium">
                                Remove
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                    {{ $product->exists ? 'Save Changes' : 'Create Product' }}
                </button>
                <a href="{{ route('admin.products.index') }}" class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</a>
            </div>
        </form>
    </div>

</x-layouts.app>
