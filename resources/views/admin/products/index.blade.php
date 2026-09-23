<x-layouts.app title="Products">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Products</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Product
        </a>
    </div>

    {{-- Search + type filter --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex-1">
            @if($typeFilter) <input type="hidden" name="type" value="{{ $typeFilter }}"> @endif
            <div class="relative">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Search by name or category…"
                       class="w-full h-10 pl-9 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @if($search)
                <a href="{{ route('admin.products.index', array_filter(['type' => $typeFilter])) }}"
                   class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
                @endif
            </div>
        </form>

        {{-- Type filter tabs --}}
        <div class="flex gap-2 shrink-0">
            @foreach(['' => 'All', 'handmade' => 'Handmade', 'sourced' => 'Sourced', 'consignment' => 'Consignment'] as $val => $label)
            <a href="{{ route('admin.products.index', array_filter(['type' => $val, 'q' => $search])) }}"
               class="px-3 py-2 rounded-xl text-xs font-semibold transition whitespace-nowrap
                      {{ $typeFilter === $val
                         ? match($val) {
                             'handmade'    => 'bg-blue-500 text-white shadow-sm',
                             'sourced'     => 'bg-purple-500 text-white shadow-sm',
                             'consignment' => 'bg-orange-500 text-white shadow-sm',
                             default       => 'bg-navy text-white shadow-sm'
                           }
                         : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    @error('delete')
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        {{ $message }}
    </div>
    @enderror

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($products->isEmpty())
            <div class="px-6 py-16 text-center">
                @if($search || $typeFilter)
                    <p class="text-sm text-gray-400">No products match your search.</p>
                    <a href="{{ route('admin.products.index') }}" class="mt-2 inline-block text-xs text-navy hover:underline">Clear filters</a>
                @else
                    <p class="text-sm text-gray-400 mb-3">No products yet.</p>
                    <a href="{{ route('admin.products.create') }}"
                       class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                        Add First Product
                    </a>
                @endif
            </div>
        @else

            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($products as $product)
                <div x-data="{ open: false }">
                    <div class="flex items-center justify-between px-4 py-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-semibold text-ink">{{ $product->name }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ match($product->source_type) { 'handmade' => 'bg-blue-100 text-blue-700', 'sourced' => 'bg-purple-100 text-purple-700', 'consignment' => 'bg-orange-100 text-orange-700', default => 'bg-gray-100 text-gray-600' } }}">
                                    {{ ucfirst($product->source_type) }}
                                </span>
                                @if($product->low_stock_count > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-700">
                                    ⚠ {{ $product->low_stock_count }} low
                                </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ₱{{ number_format($product->base_price, 2) }}
                                · {{ $product->variants_count }} {{ Str::plural('variant', $product->variants_count) }}
                                @if($product->category) · {{ $product->category }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 ml-3 shrink-0">
                            <button @click="open = !open" class="p-2 text-gray-400 hover:text-navy">
                                <svg class="w-4 h-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="h-8 px-3 rounded-lg bg-navy text-white text-xs font-semibold flex items-center">Edit</a>
                        </div>
                    </div>
                    <div x-show="open" x-cloak class="px-4 pb-4 space-y-2">
                        @foreach($product->variants as $variant)
                        <div class="flex items-center justify-between bg-sand rounded-xl px-3 py-2.5">
                            <span class="text-sm text-ink font-medium">{{ $variant->label() ?: 'Standard' }}</span>
                            <div class="flex items-center gap-2">
                                @if($variant->stock_count === 0)
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Out of stock</span>
                                @elseif($variant->isLowStock())
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">⚠ {{ $variant->stock_count }}</span>
                                @else
                                    <span class="text-xs font-semibold text-gray-600">{{ $variant->stock_count }} in stock</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        <div class="flex gap-2 pt-1">
                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="flex-1 h-9 flex items-center justify-center bg-navy text-white text-xs font-semibold rounded-xl hover:bg-navy-dark transition">
                                Edit Product
                            </a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($product->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="h-9 px-4 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold rounded-xl transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Name</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Category</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Type</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Supplier</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Base Price</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Variants</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Stock Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody x-data>
                        @foreach($products as $product)
                        <tbody x-data="{ open: false }">
                            {{-- Product row --}}
                            <tr class="hover:bg-sand/50 transition border-b border-gray-50 cursor-pointer"
                                @click="open = !open">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition"
                                             :class="open ? 'rotate-90' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="font-semibold text-ink">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500">{{ $product->category ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium
                                        {{ match($product->source_type) { 'handmade' => 'bg-blue-100 text-blue-700', 'sourced' => 'bg-purple-100 text-purple-700', 'consignment' => 'bg-orange-100 text-orange-700', default => 'bg-gray-100 text-gray-600' } }}">
                                        {{ ucfirst($product->source_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 text-xs">
                                    {{ $product->supplier?->supplier_name ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-center font-semibold text-ink">
                                    ₱{{ number_format($product->base_price, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-center text-gray-600">
                                    {{ $product->variants_count }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($product->low_stock_count > 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                            {{ $product->low_stock_count }} low
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            OK
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right" @click.stop>
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="text-xs text-navy font-semibold hover:underline mr-3">Edit</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                          class="inline"
                                          onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:underline font-medium">Delete</button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Expandable variants sub-rows --}}
                            <tr x-show="open" x-cloak>
                                <td colspan="8" class="px-0 py-0 bg-sand/30 border-b border-gray-100">
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 ml-6">Variants</p>
                                        <div class="ml-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                            @foreach($product->variants as $variant)
                                            <div class="flex items-center justify-between bg-white rounded-xl px-3 py-2.5 border border-gray-100">
                                                <div>
                                                    <p class="text-sm font-medium text-ink">{{ $variant->label() ?: 'Standard' }}</p>
                                                    <p class="text-xs text-gray-400">Reorder @ {{ $variant->reorder_level }}</p>
                                                </div>
                                                <div class="text-right ml-3 shrink-0">
                                                    @if($variant->stock_count === 0)
                                                        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Out</span>
                                                    @elseif($variant->isLowStock())
                                                        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">⚠ {{ $variant->stock_count }}</span>
                                                    @else
                                                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ $variant->stock_count }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="ml-6 mt-2">
                                            <a href="{{ route('admin.products.edit', $product) }}"
                                               class="text-xs text-navy font-medium hover:underline">
                                                Edit variants →
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $products->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
