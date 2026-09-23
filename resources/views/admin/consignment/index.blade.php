<x-layouts.app title="Consignment">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Consignment</h1>
            <p class="text-sm text-gray-500 mt-0.5">Partners, items on consignment, and payouts</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.consignment.payouts') }}"
               class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Payouts
            </a>
            <a href="{{ route('admin.consignment.partners.create') }}"
               class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">
                + Partner
            </a>
            <a href="{{ route('admin.consignment.items.create') }}"
               class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                + Add Item
            </a>
        </div>
    </div>

    {{-- Partners grid --}}
    <h2 class="text-base font-semibold text-ink mb-3">Consignment Partners</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse($partners as $partner)
        @php
            $unsettled = $unsettledByPartner[$partner->id] ?? null;
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-ink">{{ $partner->partner_name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $partner->items_count }} consignment {{ Str::plural('item', $partner->items_count) }}</p>
                    @if($partner->contact_details)
                        <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($partner->contact_details, 50) }}</p>
                    @endif
                </div>
                <a href="{{ route('admin.consignment.partners.edit', $partner) }}"
                   class="text-xs text-navy font-medium hover:underline shrink-0">Edit</a>
            </div>

            {{-- Unsettled badge --}}
            @if($unsettled && $unsettled['count'] > 0)
            <div class="flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2">
                <div>
                    <p class="text-xs font-semibold text-yellow-800">
                        {{ $unsettled['count'] }} unsettled {{ Str::plural('sale', $unsettled['count']) }}
                    </p>
                    <p class="text-xs text-yellow-700 mt-0.5">
                        ₱{{ number_format($unsettled['amount'], 2) }} owed
                    </p>
                </div>
                <a href="{{ route('admin.consignment.payouts', ['partner_id' => $partner->id]) }}"
                   class="text-xs text-yellow-700 font-semibold hover:underline">
                    Pay out →
                </a>
            </div>
            @else
            <div class="flex items-center gap-2 bg-green-50 rounded-xl px-3 py-2">
                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-xs text-green-700 font-medium">All settled</p>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center">
            <p class="text-sm text-gray-400 mb-3">No consignment partners yet.</p>
            <a href="{{ route('admin.consignment.partners.create') }}"
               class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                Add First Partner
            </a>
        </div>
        @endforelse
    </div>

    {{-- Consignment Items table --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-ink">Consignment Items</h2>
        <a href="{{ route('admin.consignment.items.create') }}"
           class="text-xs text-navy font-medium hover:underline">+ Add item</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($items->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-400">
                No consignment items recorded yet.
                <a href="{{ route('admin.consignment.items.create') }}" class="text-navy font-medium ml-1">Add one →</a>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($items as $item)
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">{{ $item->variant->displayName() }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $item->partner->partner_name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $item->units_delivered }} delivered · Received {{ $item->received_at->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-ink">₱{{ number_format($item->agreed_base_price, 2) }}</p>
                            <p class="text-xs text-gray-400">+₱{{ number_format($item->shop_markup, 2) }} markup</p>
                            <p class="text-xs font-semibold text-navy mt-0.5">Sell: ₱{{ number_format($item->agreed_base_price + $item->shop_markup, 2) }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('admin.consignment.items.edit', $item) }}"
                           class="text-xs text-navy font-medium hover:underline">Edit</a>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Partner</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Product / Variant</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Units Delivered</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Base Price</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Markup</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Selling Price</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Received</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($items as $item)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $item->partner->partner_name }}</td>
                            <td class="px-5 py-3.5 text-gray-700">{{ $item->variant->displayName() }}</td>
                            <td class="px-5 py-3.5 text-center text-gray-600">{{ $item->units_delivered }}</td>
                            <td class="px-5 py-3.5 text-right font-medium text-ink">₱{{ number_format($item->agreed_base_price, 2) }}</td>
                            <td class="px-5 py-3.5 text-right text-gray-500">₱{{ number_format($item->shop_markup, 2) }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-navy">
                                ₱{{ number_format($item->agreed_base_price + $item->shop_markup, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 text-xs">{{ $item->received_at->format('M j, Y') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.consignment.items.edit', $item) }}"
                                   class="text-xs text-navy font-medium hover:underline">Edit</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $items->links() }}</div>
            @endif
        @endif
    </div>

</x-layouts.app>
