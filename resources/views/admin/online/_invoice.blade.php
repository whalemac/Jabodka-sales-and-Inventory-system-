{{-- Order invoice fragment (A5) — rendered inside a modal, never a full page.
     Served by GET admin/online/{transaction}/invoice (route admin.online.invoice). --}}
@php
    $payLabels = \App\Models\SalesTransaction::PAYMENT_STATUSES;
    $shipLabels = \App\Models\SalesTransaction::SHIPMENT_STATUSES;
    $pay = $transaction->derivedPaymentStatus();
    $payBadge = [
        'pending'   => 'bg-gray-100 text-gray-600 border-gray-200',
        'confirmed' => 'bg-green-100 text-green-700 border-green-200',
        'refunded'  => 'bg-red-100 text-red-700 border-red-200',
    ];
    $shipBadge = [
        'pending'    => 'bg-gray-100 text-gray-600 border-gray-200',
        'preparing'  => 'bg-sky-100 text-sky-800 border-sky-200',
        'shipped'    => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'in_transit' => 'bg-purple-100 text-purple-800 border-purple-200',
        'delivered'  => 'bg-green-100 text-green-800 border-green-200',
        'returned'   => 'bg-orange-100 text-orange-800 border-orange-200',
    ];
@endphp

<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

    {{-- Header --}}
    <div class="bg-navy px-6 py-5 flex items-center justify-between">
        <div>
            <h1 class="text-white font-bold text-xl tracking-wide">JABODKA<span class="text-accent">-</span>SIMS</h1>
            <p class="text-white/70 text-xs mt-0.5">Online Order Invoice</p>
        </div>
        <p class="text-lg font-bold text-white">#{{ $transaction->id }}</p>
    </div>

    <div class="px-6 py-5">

        {{-- Customer + shipping --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5 pb-5 border-b border-dashed border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Customer</p>
                <p class="text-sm font-medium text-ink">{{ $transaction->customer?->name ?? '—' }}</p>
                @if($transaction->customer?->contact_number)
                    <p class="text-xs text-gray-400">{{ $transaction->customer->contact_number }}</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Ship To</p>
                @if($transaction->customer?->shipping_address)
                    <p class="text-sm text-ink leading-snug">{{ $transaction->customer->shipping_address }}</p>
                    @if($transaction->customer->landmark)
                        <p class="text-xs text-gray-400">{{ $transaction->customer->landmark }}</p>
                    @endif
                @else
                    <p class="text-sm text-gray-400">—</p>
                @endif
            </div>
        </div>

        {{-- Courier / tracking --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-5 pb-5 border-b border-dashed border-gray-200 text-sm">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Courier</span>
                <p class="text-sm font-medium text-ink">{{ $transaction->courier ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Tracking No.</span>
                <p class="text-sm font-medium text-ink">{{ $transaction->tracking_number ?? '—' }}</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Order Date</span>
                <p class="text-sm font-medium text-ink">{{ $transaction->transaction_date->format('M j, Y · g:i A') }}</p>
            </div>
        </div>

        {{-- Status badges --}}
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border {{ $payBadge[$pay] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                Payment: {{ $payLabels[$pay] ?? $pay }}
            </span>
            <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border {{ $shipBadge[$transaction->shipment_status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                Shipment: {{ $shipLabels[$transaction->shipment_status] ?? $transaction->shipment_status }}
            </span>
        </div>

        {{-- Line items --}}
        @if($transaction->items->isNotEmpty())
        <div class="mb-4">
            <div class="hidden sm:flex items-center gap-3 px-3 py-2 text-[11px] uppercase tracking-wide font-semibold text-gray-400 border-b border-gray-100">
                <span class="flex-1">Item</span>
                <span class="w-28 text-right">Price</span>
                <span class="w-14 text-right">Qty</span>
                <span class="w-28 text-right">Total</span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($transaction->items as $item)
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 px-3 py-3">
                    <p class="flex-1 text-sm text-ink min-w-0">{{ $item->variant->displayName() }}</p>
                    <div class="flex items-center justify-between sm:justify-end gap-3 text-xs text-gray-500 sm:w-28">
                        <span class="sm:hidden font-semibold text-gray-400">Price</span>
                        <span>₱{{ number_format($item->price_at_sale, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3 text-xs text-gray-500 sm:w-14">
                        <span class="sm:hidden font-semibold text-gray-400">Qty</span>
                        <span class="font-medium text-ink sm:font-normal sm:text-gray-500">{{ $item->quantity }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3 text-sm sm:w-28">
                        <span class="sm:hidden font-semibold text-gray-400">Total</span>
                        <span class="font-semibold text-ink">₱{{ number_format($item->price_at_sale * $item->quantity, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Totals --}}
        <div class="border-t border-dashed border-gray-200 pt-4 space-y-1.5">
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
            <div class="flex justify-between text-base font-bold text-ink pt-2 border-t border-gray-200">
                <span>TOTAL</span>
                <span>₱{{ number_format($transaction->grandTotal(), 2) }}</span>
            </div>
        </div>

        {{-- Payments --}}
        @if($transaction->payments->isNotEmpty())
        <div class="mt-5 bg-sand rounded-xl px-4 py-3 space-y-1.5">
            @foreach($transaction->payments->where('payment_status', 'confirmed') as $payment)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 capitalize">{{ $payment->payment_method === 'gcash' ? 'GCash' : 'Cash' }}</span>
                <span class="font-medium">₱{{ number_format($payment->amount_paid, 2) }}</span>
            </div>
            @if($payment->reference_number)
            <p class="text-xs text-gray-400">Ref: {{ $payment->reference_number }}</p>
            @endif
            @endforeach
            @php
                $paid = $transaction->amountPaid();
                $total = $transaction->grandTotal();
                $balance = $total - $paid;
            @endphp
            @if($balance > 0)
            <div class="flex justify-between text-sm border-t border-gray-200 pt-1.5 mt-1.5">
                <span class="text-gray-600">Balance due</span>
                <span class="font-semibold text-red-600">₱{{ number_format($balance, 2) }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Footer --}}
        <div class="text-center border-t border-dashed border-gray-200 pt-4 mt-5">
            <p class="text-xs text-gray-500 font-medium">Prepared by: {{ $transaction->user?->username }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $transaction->transaction_date->format('M j, Y g:i A') }}</p>
        </div>
    </div>
</div>