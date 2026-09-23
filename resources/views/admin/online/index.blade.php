<x-layouts.app title="Online Orders">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Online Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $orders->total() }} total orders</p>
        </div>
        <a href="{{ route('admin.online.create') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Online Order
        </a>
    </div>

    {{-- Status filter tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $filterLabels = [
                ''                => 'All',
                'pending_payment' => 'Awaiting Payment',
                'ready_to_ship'   => 'Ready to Ship',
                'shipped'         => 'Shipped',
                'delivered'       => 'Delivered',
                'cancelled'       => 'Cancelled',
            ];
            $current = request('status', '');
        @endphp
        @foreach($filterLabels as $val => $label)
        <a href="{{ route('admin.online.index', $val ? ['status' => $val] : []) }}"
           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition
                  {{ $current === $val
                       ? 'bg-navy text-white shadow-sm'
                       : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($orders->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <p class="text-sm text-gray-400 mb-4">No online orders {{ $current ? 'with this status' : 'yet' }}.</p>
                @if(!$current)
                <a href="{{ route('admin.online.create') }}"
                   class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                    Create First Order
                </a>
                @endif
            </div>
        @else
            @php
                $statusColors = [
                    'pending_payment' => 'bg-red-100 text-red-700',
                    'ready_to_ship'   => 'bg-blue-100 text-blue-700',
                    'shipped'         => 'bg-purple-100 text-purple-700',
                    'delivered'       => 'bg-green-100 text-green-700',
                    'cancelled'       => 'bg-gray-100 text-gray-500',
                ];
                $statusLabels = [
                    'pending_payment' => 'Awaiting Payment',
                    'ready_to_ship'   => 'Ready to Ship',
                    'shipped'         => 'Shipped',
                    'delivered'       => 'Delivered',
                    'cancelled'       => 'Cancelled',
                ];
            @endphp

            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($orders as $order)
                <a href="{{ route('admin.online.show', $order) }}" class="block px-4 py-4 hover:bg-sand/50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink truncate">{{ $order->customer?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $order->transaction_date->format('M j, Y · g:i A') }}</p>
                            <p class="text-xs text-gray-400">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                                @if($order->tracking_number) · {{ $order->tracking_number }} @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-ink">₱{{ number_format($order->grandTotal(), 2) }}</p>
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1
                                {{ $statusColors[$order->shipment_status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $statusLabels[$order->shipment_status] ?? $order->shipment_status }}
                            </span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Customer</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Items</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Total</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Tracking</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orders as $order)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap text-xs">
                                {{ $order->transaction_date->format('M j, Y') }}<br>
                                <span class="text-gray-400">{{ $order->transaction_date->format('g:i A') }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-ink">{{ $order->customer?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $order->customer?->contact_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($order->grandTotal(), 2) }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full
                                    {{ $statusColors[$order->shipment_status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusLabels[$order->shipment_status] ?? $order->shipment_status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-500">
                                {{ $order->tracking_number ?? '—' }}
                                @if($order->courier)<br><span class="text-gray-400">{{ $order->courier }}</span>@endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.online.show', $order) }}"
                                   class="inline-flex items-center gap-1 text-xs text-navy font-semibold hover:underline">
                                    View
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
