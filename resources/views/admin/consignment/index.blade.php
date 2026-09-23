<x-layouts.app title="Consignment">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-ink">Consignment</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.consignment.partners.create') }}" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">+ Partner</a>
            <a href="{{ route('admin.consignment.items.create') }}" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">+ Item</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach($partners as $partner)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-start justify-between">
            <div>
                <p class="font-semibold text-ink">{{ $partner->partner_name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $partner->items_count }} {{ Str::plural('item', $partner->items_count) }}</p>
                @if($partner->contact_details)<p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($partner->contact_details, 40) }}</p>@endif
            </div>
            <a href="{{ route('admin.consignment.partners.edit', $partner) }}" class="text-xs text-navy font-medium hover:underline ml-3">Edit</a>
        </div>
        @endforeach
        @if($partners->isEmpty())
        <div class="col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-sm text-gray-400">
            No consignment partners yet. <a href="{{ route('admin.consignment.partners.create') }}" class="text-navy font-medium">Add one →</a>
        </div>
        @endif
    </div>

    <h2 class="text-base font-semibold text-ink mb-3">Consignment Items</h2>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($items->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">No consignment items yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-sand border-b border-gray-100">
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Partner</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Delivered</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Base Price</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Markup</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Received</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                @foreach($items as $item)
                <tr class="hover:bg-sand/50 transition">
                    <td class="px-5 py-3.5 font-medium text-ink">{{ $item->partner->partner_name }}</td>
                    <td class="px-5 py-3.5 text-gray-700">{{ $item->variant->displayName() }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $item->units_delivered }}</td>
                    <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($item->agreed_base_price, 2) }}</td>
                    <td class="px-5 py-3.5 text-gray-600">₱{{ number_format($item->shop_markup, 2) }}</td>
                    <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $item->received_at->format('M j, Y') }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $items->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
