<x-layouts.app title="Suppliers">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-ink">Suppliers</h1>
        <a href="{{ route('admin.suppliers.create') }}" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">+ Add Supplier</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($suppliers->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No suppliers yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Supplier Name</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Contact</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Products</th>
                    <th class="px-5 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($suppliers as $supplier)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $supplier->supplier_name }}</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $supplier->contact_details ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $supplier->products_count }}</td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-xs text-navy font-medium hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="inline ml-3" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $suppliers->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
