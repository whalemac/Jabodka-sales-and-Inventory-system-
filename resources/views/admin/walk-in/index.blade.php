<x-layouts.app title="Walk-In Orders">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Walk-In Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $sales->total() }} total transactions</p>
        </div>
        <a href="{{ route('admin.walk-in.create') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Sale
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($sales->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
                <p class="text-sm text-gray-400 mb-4">No walk-in sales recorded yet.</p>
                <a href="{{ route('admin.walk-in.create') }}"
                   class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                    Record First Sale
                </a>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($sales as $sale)
                <div class="px-4 py-4" x-data="{ expanded: false }">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">
                                {{ $sale->customer?->name ?? 'Anonymous' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $sale->transaction_date->format('M j, Y · g:i A') }}
                                · {{ $sale->items->count() }} {{ Str::plural('item', $sale->items->count()) }}
                            </p>
                        </div>
                        <div class="ml-3 text-right shrink-0">
                            <p class="text-sm font-bold text-ink">₱{{ number_format($sale->grandTotal(), 2) }}</p>
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $sale->isPaid() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $sale->isPaid() ? 'Paid' : 'Unpaid' }}
                            </span>
                        </div>
                    </div>
                    <button @click="expanded = !expanded" class="mt-2 text-xs text-navy font-medium">
                        <span x-text="expanded ? 'Hide items ▲' : 'View items ▼'"></span>
                    </button>
                    <div x-show="expanded" x-cloak class="mt-2 space-y-1">
                        @foreach($sale->items as $item)
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>{{ $item->variant->displayName() }} × {{ $item->quantity }}</span>
                            <span>₱{{ number_format($item->price_at_sale * $item->quantity, 2) }}</span>
                        </div>
                        @endforeach
                        @if($sale->receipt)
                        <a href="{{ route('admin.receipts.show', $sale->receipt) }}"
                           class="inline-flex items-center gap-1 text-xs text-navy font-medium mt-2 hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            View Receipt ({{ $sale->receipt->receipt_number }})
                        </a>
                        @else
                        <form method="POST" action="{{ route('admin.receipts.issue', $sale) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-gray-500 hover:text-navy font-medium mt-2">Issue receipt</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Date & Time</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Customer</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Items</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Total</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($sales as $sale)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">
                                {{ $sale->transaction_date->format('M j, Y') }}<br>
                                <span class="text-xs text-gray-400">{{ $sale->transaction_date->format('g:i A') }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-ink">
                                {{ $sale->customer?->name ?? 'Anonymous' }}
                                @if($sale->customer?->contact_number)
                                <br><span class="text-xs text-gray-400">{{ $sale->customer->contact_number }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                {{ $sale->items->count() }} {{ Str::plural('item', $sale->items->count()) }}
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-ink whitespace-nowrap">
                                ₱{{ number_format($sale->grandTotal(), 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full
                                    {{ $sale->isPaid() ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $sale->isPaid() ? 'Paid' : 'Unpaid' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if($sale->receipt)
                                <a href="{{ route('admin.receipts.show', $sale->receipt) }}"
                                   class="text-xs text-navy font-medium hover:underline">
                                    {{ $sale->receipt->receipt_number }}
                                </a>
                                @else
                                <form method="POST" action="{{ route('admin.receipts.issue', $sale) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-gray-500 hover:text-navy font-medium hover:underline">
                                        Issue receipt
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sales->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $sales->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
