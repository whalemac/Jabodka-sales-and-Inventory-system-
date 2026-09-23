<x-layouts.app title="Products">
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-ink">Products</h1>
    <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">+ New Product</a>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if($products->isEmpty())
        <div class="px-6 py-12 text-center text-sm text-gray-400">No products yet. <a href="{{ route('admin.products.create') }}" class="text-navy font-medium">Add one →</a></div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-sand border-b border-gray-100">
                <th class="text-left px-5 py-3 font-semibold text-gray-600">Name</th>
                <th class="text-left px-5 py-3 font-semibold text-gray-600">Category</th>
                <th class="text-left px-5 py-3 font-semibold text-gray-600">Type</th>
                <th class="text-left px-5 py-3 font-semibold text-gray-600">Base Price</th>
                <th class="text-left px-5 py-3 font-semibold text-gray-600">Variants</th>
                <th class="px-5 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
            @foreach($products as $product)
            <tr class="hover:bg-sand/50 transition">
                <td class="px-5 py-3.5 font-medium text-ink">{{ $product->name }}</td>
                <td class="px-5 py-3.5 text-gray-500">{{ $product->category ?? '—' }}</td>
                <td class="px-5 py-3.5"><span class="inline-block text-xs px-2.5 py-0.5 rounded-full font-medium {{ match($product->source_type) { 'handmade' => 'bg-blue-100 text-blue-700', 'sourced' => 'bg-purple-100 text-purple-700', 'consignment' => 'bg-orange-100 text-orange-700', default => 'bg-gray-100 text-gray-600' } }}">{{ ucfirst($product->source_type) }}</span></td>
                <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($product->base_price, 2) }}</td>
                <td class="px-5 py-3.5 text-gray-600">{{ $product->variants_count }}</td>
                <td class="px-5 py-3.5 text-right">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-xs text-navy font-medium hover:underline">Edit</a>
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline ml-3" onsubmit="return confirm('Delete this product?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $products->links() }}</div>@endif
    @endif
</div>
</x-layouts.app>
