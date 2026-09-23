<x-layouts.app title="Online Order #{{ $transaction->id }}">

    <div class="mb-6">
        <a href="{{ route('admin.online.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Online Orders
        </a>
        <h1 class="text-2xl font-bold text-ink">Online Order #{{ $transaction->id }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $transaction->transaction_date->format('F j, Y · g:i A') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Order items + totals --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Items</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($transaction->items as $item)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-ink">{{ $item->variant->displayName() }}</p>
                            <p class="text-xs text-gray-400">₱{{ number_format($item->price_at_sale, 2) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="font-semibold text-ink">₱{{ number_format($item->price_at_sale * $item->quantity, 2) }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 px-5 py-4 bg-sand/50 space-y-1">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span><span>₱{{ number_format($transaction->itemsTotal(), 2) }}</span>
                    </div>
                    @if($transaction->shipping_fee)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping ({{ $transaction->courier }})</span>
                        <span>₱{{ number_format($transaction->shipping_fee, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-base text-ink border-t border-gray-200 pt-2">
                        <span>Grand Total</span><span>₱{{ number_format($transaction->grandTotal(), 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Confirm payment form --}}
            @if(!$transaction->isPaid())
            <div class="bg-white rounded-2xl shadow-sm border border-yellow-200 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Confirm Payment</h2>
                <form method="POST" action="{{ route('admin.payments.confirm', $transaction) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Method</label>
                            <select name="payment_method" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Amount Paid (₱)</label>
                            <input type="number" step="0.01" name="amount_paid" required
                                   value="{{ $transaction->grandTotal() }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Reference # (GCash)</label>
                        <input type="text" name="reference_number" placeholder="Optional"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <button type="submit" class="w-full h-11 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-xl transition">
                        Confirm Payment
                    </button>
                </form>
            </div>
            @endif

            {{-- Shipment update --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Update Shipment</h2>
                <form method="POST" action="{{ route('admin.online.shipment', $transaction) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                            <select name="shipment_status" required
                                    class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                @foreach(['pending_payment','ready_to_ship','shipped','delivered','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $transaction->shipment_status === $s ? 'selected' : '' }}>
                                    {{ Str::title(str_replace('_', ' ', $s)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Courier</label>
                            <input type="text" name="courier" value="{{ $transaction->courier }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tracking #</label>
                            <input type="text" name="tracking_number" value="{{ $transaction->tracking_number }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>
                    </div>
                    @error('shipment_status')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="h-11 px-6 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        Update Shipment
                    </button>
                </form>
            </div>
        </div>

        {{-- Sidebar: customer + payment info --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-3">Customer</h2>
                <p class="text-sm font-medium text-ink">{{ $transaction->customer?->name ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $transaction->customer?->contact_number }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $transaction->customer?->shipping_address }}</p>
                @if($transaction->customer?->landmark)
                <p class="text-xs text-gray-400 mt-0.5">Near: {{ $transaction->customer->landmark }}</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-3">Payments</h2>
                @forelse($transaction->payments as $payment)
                <div class="flex items-center justify-between text-sm mb-2">
                    <div>
                        <p class="font-medium text-ink">{{ $payment->payment_method === 'gcash' ? 'GCash' : 'Cash' }}</p>
                        @if($payment->reference_number)
                        <p class="text-xs text-gray-400">Ref: {{ $payment->reference_number }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="font-semibold">₱{{ number_format($payment->amount_paid, 2) }}</p>
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $payment->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($payment->payment_status) }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400">No payments recorded.</p>
                @endforelse
            </div>

            @if($transaction->receipt)
            <a href="{{ route('admin.receipts.show', $transaction->receipt) }}"
               class="flex items-center gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-navy transition">
                <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-navy">{{ $transaction->receipt->receipt_number }}</p>
                    <p class="text-xs text-gray-400">View receipt</p>
                </div>
            </a>
            @else
            <form method="POST" action="{{ route('admin.receipts.issue', $transaction) }}">
                @csrf
                <button type="submit" class="w-full h-11 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium rounded-2xl transition">
                    Issue Receipt
                </button>
            </form>
            @endif
        </div>
    </div>

</x-layouts.app>
