<x-layouts.app title="Raw Materials">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-ink">Raw Materials</h1>
        <a href="{{ route('staff.materials.create') }}" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">+ Add Material</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($materials->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No raw materials yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Material</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Unit</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Stock</th>
                    <th class="px-5 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($materials as $mat)
                <tr class="hover:bg-sand/50 transition" x-data="{ receiving: false, qty: 1 }">
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $mat->material_name }}</td>
                    <td class="px-5 py-3.5 text-gray-500">{{ $mat->unit }}</td>
                    <td class="px-5 py-3.5 font-semibold text-ink">{{ number_format($mat->stock_quantity, 2) }}</td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="receiving = !receiving" class="text-xs text-green-600 font-medium hover:underline">Receive</button>
                            <a href="{{ route('staff.materials.edit', $mat) }}" class="text-xs text-navy font-medium hover:underline">Edit</a>
                        </div>
                        <div x-show="receiving" x-cloak class="mt-2 flex gap-2 justify-end">
                            <form method="POST" action="{{ route('staff.materials.receive', $mat) }}" class="flex gap-2">
                                @csrf
                                <input type="number" name="quantity" x-model.number="qty" min="0.01" step="0.01"
                                       class="w-24 h-8 px-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-navy">
                                <button type="submit" class="h-8 px-3 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition">Save</button>
                                <button type="button" @click="receiving = false" class="h-8 px-2 text-gray-400 hover:text-gray-600 text-xs">Cancel</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-layouts.app>
