<x-layouts.app title="Stock Management">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Stock Management</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $totalVariants }} {{ Str::plural('variant', $totalVariants) }} total
                @if($lowStockCount > 0)
                    ·
                    <a href="{{ route('admin.stock.index', array_filter(['filter' => 'low', 'type' => $type ?: null])) }}"
                       class="text-red-600 font-semibold hover:underline">
                        {{ $lowStockCount }} need restocking
                    </a>
                @else
                    · <span class="text-green-600 font-medium">All levels healthy</span>
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

    {{-- Source type filter --}}
    <div class="flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'handmade' => 'Handmade', 'sourced' => 'Sourced', 'consignment' => 'Consignment'] as $val => $label)
        <a href="{{ route('admin.stock.index', array_filter(['q' => $search, 'filter' => $filter !== 'all' ? $filter : null, 'type' => $val ?: null])) }}"
           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition
                  {{ $type === $val
                     ? match($val) {
                         'handmade' => 'bg-blue-500 text-white shadow-sm',
                         'sourced' => 'bg-purple-500 text-white shadow-sm',
                         'consignment' => 'bg-orange-500 text-white shadow-sm',
                         default => 'bg-navy text-white shadow-sm',
                       }
                     : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Search + filter bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        {{-- Search --}}
        <form method="GET" action="{{ route('admin.stock.index') }}" class="flex-1">
            @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
            @if($type) <input type="hidden" name="type" value="{{ $type }}"> @endif
            <div class="relative">
                <input type="text" name="q" value="{{ $search }}"
                       placeholder="Search product name, size, version, or category…"
                       class="w-full h-10 pl-9 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @if($search)
                <a href="{{ route('admin.stock.index', array_filter(['filter' => request('filter'), 'type' => $type ?: null])) }}"
                   class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
                @endif
            </div>
        </form>

        {{-- Filter tabs --}}
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('admin.stock.index', array_filter(['q' => $search, 'filter' => 'all', 'type' => $type ?: null])) }}"
               class="px-4 py-2 rounded-xl text-sm font-medium transition
                      {{ $filter === 'all' ? 'bg-navy text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
                All
            </a>
            <a href="{{ route('admin.stock.index', array_filter(['q' => $search, 'filter' => 'low', 'type' => $type ?: null])) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold transition
                      {{ $filter === 'low' ? 'bg-red-500 text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-red-300' }}">
                @if($lowStockCount > 0)
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold
                             {{ $filter === 'low' ? 'bg-white/30 text-white' : 'bg-red-100 text-red-600' }}">
                    {{ $lowStockCount }}
                </span>
                @endif
                Low Stock
            </a>
        </div>
    </div>

    {{-- Supplier invoices --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-ink text-sm">Supplier invoices</h2>
                <p class="text-xs text-gray-400 mt-0.5">Click an invoice to see every product received on that delivery</p>
            </div>
            <a href="{{ route('admin.stock.import-form') }}" class="text-xs text-navy font-semibold hover:underline">Record invoice →</a>
        </div>
        @if($receipts->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-gray-400">No supplier invoices recorded yet.</div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($receipts as $receipt)
                <div x-data="{ open: false }" class="px-5 py-3.5">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-3 text-left">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">
                                Invoice {{ $receipt->invoice_number }}
                                <span class="font-normal text-gray-400">· {{ $receipt->supplier?->supplier_name ?? 'No supplier' }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $receipt->received_date->format('M j, Y') }}
                                · {{ $receipt->items->count() }} {{ Str::plural('line', $receipt->items->count()) }}
                                · {{ $receipt->user->username }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-sm font-semibold text-ink">₱{{ number_format($receipt->total_amount, 2) }}</span>
                            <svg class="w-4 h-4 text-gray-400 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <div x-show="open" x-cloak class="mt-3 rounded-xl border border-gray-100 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-sand border-b-2 border-gray-200 text-left text-gray-500">
                                    <th class="px-3 py-2 font-semibold">Product / Variant</th>
                                    <th class="px-3 py-2 font-semibold text-center">Qty</th>
                                    <th class="px-3 py-2 font-semibold text-right">Unit cost</th>
                                    <th class="px-3 py-2 font-semibold text-right">Line total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($receipt->items as $line)
                                <tr>
                                    <td class="px-3 py-2 text-ink">{{ $line->variant->displayName() }}</td>
                                    <td class="px-3 py-2 text-center">{{ $line->quantity_received }}</td>
                                    <td class="px-3 py-2 text-right">{{ $line->unit_cost !== null ? '₱'.number_format($line->unit_cost, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium">{{ $line->line_total !== null ? '₱'.number_format($line->line_total, 2) : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($receipt->notes)
                        <p class="px-3 py-2 text-xs text-gray-500 bg-sand/40">{{ $receipt->notes }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            @if($receipts->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $receipts->links() }}</div>
            @endif
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($variants->isEmpty())
            <div class="px-6 py-16 text-center">
                @if($search)
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-600">No results for "{{ $search }}"</p>
                    <a href="{{ route('admin.stock.index') }}" class="mt-2 inline-block text-xs text-navy hover:underline">Clear search</a>
                @elseif($filter === 'low')
                    <svg class="w-12 h-12 text-green-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-600">All stock levels are healthy!</p>
                    <p class="text-xs text-gray-400 mt-1">No variants are at or below their reorder level.</p>
                @else
                    <p class="text-sm text-gray-400">No products found.</p>
                    <a href="{{ route('admin.products.create') }}" class="mt-3 inline-block text-sm text-navy font-medium hover:underline">Add a product →</a>
                @endif
            </div>
        @else

            {{-- ===== MOBILE CARDS ===== --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($variants as $variant)
                @php
                    $isOut = $variant->stock_count === 0;
                    $isLow = !$isOut && $variant->isLowStock();
                @endphp
                <div class="px-4 py-3.5 {{ $isOut ? 'bg-red-50/40' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <p class="text-sm font-semibold text-ink">{{ $variant->product->name }}</p>
                                <span class="text-xs px-1.5 py-0.5 rounded font-medium
                                    {{ match($variant->product->source_type) {
                                        'handmade'    => 'bg-blue-100 text-blue-700',
                                        'sourced'     => 'bg-purple-100 text-purple-700',
                                        'consignment' => 'bg-orange-100 text-orange-700',
                                        default       => 'bg-gray-100 text-gray-600'
                                    } }}">
                                    {{ ucfirst($variant->product->source_type) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $variant->label() }}</p>
                            @if($variant->product->supplier)
                                <p class="text-xs text-gray-400">{{ $variant->product->supplier->supplier_name }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0 space-y-1">
                            @if($isOut)
                                <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">Out of stock</span>
                            @elseif($isLow)
                                <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">⚠ {{ $variant->stock_count }} left</span>
                            @else
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">{{ $variant->stock_count }} in stock</span>
                            @endif
                            <p class="text-xs text-gray-400">Reorder @ {{ $variant->reorder_level }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2.5">
                        <a href="{{ route('admin.stock.adjust-form', $variant) }}"
                           class="flex-1 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-medium text-ink transition">
                            Adjust
                        </a>
                        <a href="{{ route('admin.stock.import-form', ['variant' => $variant->id]) }}"
                           class="flex-1 h-8 flex items-center justify-center rounded-lg bg-navy/10 hover:bg-navy text-xs font-semibold text-navy hover:text-white transition">
                            + Restock
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ===== DESKTOP TABLE ===== --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b-2 border-gray-200 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Product</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Variant</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Type</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Supplier</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">In Stock</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Reorder At</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Status</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($variants as $variant)
                        @php
                            $isOut = $variant->stock_count === 0;
                            $isLow = !$isOut && $variant->isLowStock();
                        @endphp
                        <tr class="hover:bg-sand/50 transition {{ $isOut ? 'bg-red-50/30' : '' }}">
                            <td class="px-5 py-3.5 font-medium text-ink">
                                {{ $variant->product->name }}
                                @if($variant->product->category)
                                <br><span class="text-xs text-gray-400 font-normal">{{ $variant->product->category }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $variant->label() ?: '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ match($variant->product->source_type) {
                                        'handmade'    => 'bg-blue-100 text-blue-700',
                                        'sourced'     => 'bg-purple-100 text-purple-700',
                                        'consignment' => 'bg-orange-100 text-orange-700',
                                        default       => 'bg-gray-100 text-gray-600'
                                    } }}">
                                    {{ ucfirst($variant->product->source_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 text-xs">
                                {{ $variant->product->supplier?->supplier_name ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-base font-bold
                                    {{ $isOut ? 'text-red-600' : ($isLow ? 'text-yellow-600' : 'text-ink') }}">
                                    {{ $variant->stock_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center text-gray-500">
                                {{ $variant->reorder_level }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($isOut)
                                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                                        Out of stock
                                    </span>
                                @elseif($isLow)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 shrink-0"></span>
                                        ⚠ Low
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                                        OK
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="{{ route('admin.stock.import-form', ['variant' => $variant->id]) }}"
                                       class="inline-flex items-center gap-1 text-xs text-navy font-semibold hover:underline
                                              {{ $isOut || $isLow ? 'text-navy' : 'text-gray-500 hover:text-navy' }}">
                                        + Restock
                                    </a>
                                    <a href="{{ route('admin.stock.adjust-form', $variant) }}"
                                       class="inline-flex items-center gap-1 text-xs text-gray-500 font-medium hover:text-navy hover:underline">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Adjust
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($variants->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $variants->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
