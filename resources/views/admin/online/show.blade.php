<x-layouts.app title="Online Order #{{ $transaction->id }}">

    @php
        $statusColors = [
            'pending_payment' => 'bg-red-100 text-red-700 border-red-200',
            'ready_to_ship'   => 'bg-blue-100 text-blue-700 border-blue-200',
            'shipped'         => 'bg-purple-100 text-purple-700 border-purple-200',
            'delivered'       => 'bg-green-100 text-green-700 border-green-200',
            'cancelled'       => 'bg-gray-100 text-gray-500 border-gray-200',
        ];
        $statusLabels = [
            'pending_payment' => 'Awaiting Payment',
            'ready_to_ship'   => 'Ready to Ship',
            'shipped'         => 'Shipped',
            'delivered'       => 'Delivered',
            'cancelled'       => 'Cancelled',
        ];
        $statusColor = $statusColors[$transaction->shipment_status] ?? 'bg-gray-100 text-gray-600';
        $statusLabel = $statusLabels[$transaction->shipment_status] ?? $transaction->shipment_status;
    @endphp

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.online.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Online Orders
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-ink">Order #{{ $transaction->id }}</h1>
                <span class="inline-block text-sm font-semibold px-3 py-1 rounded-full border {{ $statusColor }}">
                    {{ $statusLabel }}
                </span>
                @if($transaction->isPaid())
                <span class="inline-block text-sm font-semibold px-3 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">
                    ✓ Paid
                </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">
                {{ $transaction->transaction_date->format('F j, Y · g:i A') }}
                · by {{ $transaction->user->username }}
            </p>
        </div>

        {{-- Quick receipt button --}}
        @if($transaction->receipt)
        <a href="{{ route('admin.receipts.show', $transaction->receipt) }}"
           class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition self-start shrink-0">
            <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ $transaction->receipt->receipt_number }}
        </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ===== MAIN COLUMN ===== --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Order Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Order Items</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($transaction->items as $item)
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink">{{ $item->variant->displayName() }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                ₱{{ number_format($item->price_at_sale, 2) }} × {{ $item->quantity }}
                            </p>
                        </div>
                        <p class="font-semibold text-ink ml-4 shrink-0">
                            ₱{{ number_format($item->price_at_sale * $item->quantity, 2) }}
                        </p>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 px-5 py-4 bg-sand/50 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Subtotal</span>
                        <span>₱{{ number_format($transaction->itemsTotal(), 2) }}</span>
                    </div>
                    @if($transaction->shipping_fee > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping ({{ $transaction->courier ?? 'Courier' }})</span>
                        <span>₱{{ number_format($transaction->shipping_fee, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-base text-ink pt-2 border-t border-gray-200">
                        <span>Grand Total</span>
                        <span>₱{{ number_format($transaction->grandTotal(), 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Confirm Payment (only if unpaid) --}}
            @if(!$transaction->isPaid())
            <div class="bg-white rounded-2xl shadow-sm border border-yellow-300 overflow-hidden"
                 x-data="{ method: 'gcash' }">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-yellow-100 bg-yellow-50">
                    <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Payment Pending</p>
                        <p class="text-xs text-yellow-700">This order cannot be shipped until payment is confirmed.</p>
                    </div>
                </div>
                <div class="p-5">
                    <h2 class="font-semibold text-ink text-sm mb-4">Confirm Payment</h2>
                    <form method="POST" action="{{ route('admin.payments.confirm', $transaction) }}"
                          class="space-y-3">
                        @csrf
                        {{-- Method --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Payment Method</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="gcash"
                                           x-model="method" class="sr-only">
                                    <div :class="method === 'gcash' ? 'border-navy bg-navy/5 text-navy' : 'border-gray-200 text-gray-600'"
                                         class="h-11 rounded-xl border-2 text-sm font-semibold transition flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        GCash
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="payment_method" value="cash"
                                           x-model="method" class="sr-only">
                                    <div :class="method === 'cash' ? 'border-navy bg-navy/5 text-navy' : 'border-gray-200 text-gray-600'"
                                         class="h-11 rounded-xl border-2 text-sm font-semibold transition flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Cash on Delivery
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                Amount Paid (₱)
                            </label>
                            <input type="number" step="0.01" name="amount_paid" required
                                   value="{{ number_format($transaction->grandTotal(), 2, '.', '') }}"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>

                        {{-- GCash reference — only shown for GCash --}}
                        <div x-show="method === 'gcash'" x-cloak>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">
                                GCash Reference # <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="reference_number"
                                   placeholder="e.g. 09123456789"
                                   class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        </div>

                        @error('payment_method')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <button type="submit"
                                class="w-full h-11 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-xl transition">
                            ✓ Confirm Payment
                        </button>
                    </form>
                </div>
            </div>
            @else
            {{-- Payment already confirmed banner --}}
            <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-4">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">Payment Confirmed</p>
                    <p class="text-xs text-green-700">
                        ₱{{ number_format($transaction->amountPaid(), 2) }} received
                        @php $confirmedPayment = $transaction->payments->where('payment_status','confirmed')->first(); @endphp
                        @if($confirmedPayment)
                            via {{ $confirmedPayment->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                            @if($confirmedPayment->reference_number) · Ref: {{ $confirmedPayment->reference_number }} @endif
                        @endif
                    </p>
                </div>
            </div>
            @endif

            {{-- Shipment Update --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Update Shipment</h2>
                    @if(!$transaction->isPaid())
                    <p class="text-xs text-yellow-600 mt-1 font-medium">
                        ⚠ Payment must be confirmed before marking as Ready to Ship or Shipped.
                    </p>
                    @endif
                </div>
                <div class="p-5">
                    <form method="POST" action="{{ route('admin.online.shipment', $transaction) }}"
                          class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    Shipment Status
                                </label>
                                <select name="shipment_status" required
                                        class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                                    @foreach([
                                        'pending_payment' => 'Awaiting Payment',
                                        'ready_to_ship'   => 'Ready to Ship',
                                        'shipped'         => 'Shipped',
                                        'delivered'       => 'Delivered',
                                        'cancelled'       => 'Cancelled',
                                    ] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ $transaction->shipment_status === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('shipment_status')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Courier</label>
                                <input type="text" name="courier"
                                       value="{{ old('courier', $transaction->courier) }}"
                                       placeholder="e.g. J&T Express"
                                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tracking #</label>
                                <input type="text" name="tracking_number"
                                       value="{{ old('tracking_number', $transaction->tracking_number) }}"
                                       placeholder="e.g. 420123456789"
                                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            </div>
                        </div>

                        <button type="submit"
                                class="h-11 px-6 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                            Update Shipment
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- ===== SIDEBAR ===== --}}
        <div class="space-y-4">

            {{-- Customer details --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-3">Customer</h2>
                <div class="space-y-2">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="text-sm font-medium text-ink">{{ $transaction->customer?->name ?? '—' }}</p>
                    </div>
                    @if($transaction->customer?->contact_number)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <p class="text-sm text-gray-700">{{ $transaction->customer->contact_number }}</p>
                    </div>
                    @endif
                    @if($transaction->customer?->shipping_address)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-700">{{ $transaction->customer->shipping_address }}</p>
                            @if($transaction->customer->landmark)
                            <p class="text-xs text-gray-400 mt-0.5">Near: {{ $transaction->customer->landmark }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Payment history --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-3">Payment History</h2>
                @forelse($transaction->payments as $payment)
                <div class="flex items-start justify-between text-sm mb-3 last:mb-0">
                    <div>
                        <p class="font-medium text-ink">
                            {{ $payment->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                        </p>
                        @if($payment->reference_number)
                        <p class="text-xs text-gray-400 mt-0.5">Ref: {{ $payment->reference_number }}</p>
                        @endif
                        @if($payment->payment_date)
                        <p class="text-xs text-gray-400">{{ $payment->payment_date->format('M j, Y g:i A') }}</p>
                        @endif
                    </div>
                    <div class="text-right ml-3 shrink-0">
                        <p class="font-semibold text-ink">₱{{ number_format($payment->amount_paid, 2) }}</p>
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full mt-1
                            {{ $payment->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($payment->payment_status) }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400">No payments recorded yet.</p>
                @endforelse
            </div>

            {{-- Receipt --}}
            @if($transaction->receipt)
            <a href="{{ route('admin.receipts.show', $transaction->receipt) }}"
               class="flex items-center gap-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-navy transition group">
                <div class="w-10 h-10 rounded-xl bg-navy/10 group-hover:bg-navy flex items-center justify-center transition shrink-0">
                    <svg class="w-5 h-5 text-navy group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-navy">{{ $transaction->receipt->receipt_number }}</p>
                    <p class="text-xs text-gray-400">View / print receipt</p>
                </div>
            </a>
            @else
            <form method="POST" action="{{ route('admin.receipts.issue', $transaction) }}">
                @csrf
                <button type="submit"
                        class="w-full h-11 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium rounded-2xl transition">
                    Issue Receipt
                </button>
            </form>
            @endif

            {{-- Shipment status timeline --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Order Timeline</h2>
                @php
                    $steps = [
                        'pending_payment' => 'Awaiting Payment',
                        'ready_to_ship'   => 'Ready to Ship',
                        'shipped'         => 'Shipped',
                        'delivered'       => 'Delivered',
                    ];
                    $statuses = array_keys($steps);
                    $currentIdx = array_search($transaction->shipment_status, $statuses);
                @endphp
                @if($transaction->shipment_status === 'cancelled')
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="w-3 h-3 rounded-full bg-gray-300 shrink-0"></span>
                    Order was cancelled
                </div>
                @else
                <div class="space-y-3">
                    @foreach($steps as $stepStatus => $stepLabel)
                    @php
                        $stepIdx = array_search($stepStatus, $statuses);
                        $done    = $stepIdx <= $currentIdx;
                        $active  = $stepIdx === $currentIdx;
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full shrink-0 {{ $active ? 'bg-navy ring-4 ring-navy/20' : ($done ? 'bg-green-500' : 'bg-gray-200') }}"></div>
                        <span class="text-sm {{ $active ? 'font-semibold text-navy' : ($done ? 'text-gray-700' : 'text-gray-400') }}">
                            {{ $stepLabel }}
                        </span>
                        @if($active)
                        <span class="ml-auto text-xs text-navy font-semibold">← Current</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>

</x-layouts.app>
