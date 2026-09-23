<x-layouts.app title="Stock Management">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Stock Management</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Live inventory levels across all products
                @if($lowStockCount > 0)
                    · <span class="text-red-600 font-semibold">{{ $lowStockCount }} low-stock {{ Str::plural('alert', $lowStockCount) }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.stock.import-form') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
            </svg>
            Add Stock (Import)
        </a>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-2 mb-4">
        <a href="{{ route('admin.stock.index', ['filter' => 'all']) }}"
           class="px-4 py-2 rounded-xl text-sm font-medium transition
                  {{ $filter === 'all' ? 'bg-navy text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
            All Products
        </a>
        <a href="{{ route('admin.stock.index', ['filter' => 'low']) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition
                  {{ $filter === 'low' ? 'bg-red-500 text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-red-400' }}">
            @if($lowStockCount > 0)
                <span class="w-5 h-5 rounded-full bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center {{ $filter === 'low' ? 'bg-red-400 text-white' : '' }}">
                    {{ $lowStockCount }}
                </span>
            @endif
            Low Stock
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($variants->isEmpty())
            <div class="px-6 py-16 text-center">
                @if($filter === 'low')
                    <svg class="w-12 h-12 text-green-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-600">All stock levels are healthy!</p>
                    <p class="text-xs text-gray-400 mt-1">No variants are at or below their reorder level.</p>
                @else
                    <p class="text-sm text-gray-400">No products found. Add products first.</p>
                    <a href="{{ route('admin.products.create') }}" class="mt-3 inline-block text-sm text-navy font-medium hover:underline">Add a product →</a>
                @endif
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($variants as $variant)
                <div class="px-4 py-3.5 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-ink truncate">{{ $variant->product->name }}</p>
                        <p class="text-xs text-gray-400">{{ $variant->label() }}</p>
                        @if($variant->product->supplier)
                            <p class="text-xs text-gray-400">{{ $variant->product->supplier->supplier_name }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <div class="text-right">
                            @if($variant->isLowStock())
                                <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full
                                    {{ $variant->stock_count === 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $variant->stock_count === 0 ? 'Out of stock' : $variant->stock_count.' left ⚠' }}
                                </span>
                            @else
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">
                                    {{ $variant->stock_count }} in stock
                                </span>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">Reorder @ {{ $variant->reorder_level }}</p>
                        </div>
                        <a href="{{ route('admin.stock.adjust-form', $variant) }}"
                           class="w-8 h-8 rounded-lg bg-navy/10 hover:bg-navy flex items-center justify-center text-navy hover:text-white transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Product</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Variant</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Type</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Supplier</th>
                            <th class="text-center px-5 py-3 font-semibold text-gray-600">In Stock</th>
                            <th class="text-center px-5 py-3 font-semibold text-gray-600">Reorder At</th>
                            <th class="text-center px-5 py-3 font-semibold text-gray-600">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($variants as $variant)
                        <tr class="hover:bg-sand/50 transition {{ $variant->stock_count === 0 ? 'bg-red-50/30' : '' }}">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $variant->product->name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $variant->label() }}</td>
                            <td class="px-5 py-3.5">
                                @php
                                    $typeColor = match($variant->product->source_type) {
                                        'handmade'     => 'bg-blue-100 text-blue-700',
                                        'sourced'      => 'bg-purple-100 text-purple-700',
                                        'consignment'  => 'bg-orange-100 text-orange-700',
                                        default        => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium {{ $typeColor }}">
                                    {{ ucfirst($variant->product->source_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 text-xs">
                                {{ $variant->product->supplier?->supplier_name ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-center font-bold
                                {{ $variant->stock_count === 0 ? 'text-red-600' : ($variant->isLowStock() ? 'text-yellow-600' : 'text-ink') }}">
                                {{ $variant->stock_count }}
                            </td>
                            <td class="px-5 py-3.5 text-center text-gray-500">{{ $variant->reorder_level }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @if($variant->stock_count === 0)
                                    <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">Out of stock</span>
                                @elseif($variant->isLowStock())
                                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">⚠ Low</span>
                                @else
                                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">OK</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.stock.adjust-form', $variant) }}"
                                   class="inline-flex items-center gap-1.5 text-xs text-navy font-medium hover:underline">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Adjust
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($variants->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $variants->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
