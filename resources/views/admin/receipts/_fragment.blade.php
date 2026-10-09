{{-- ===== RECEIPT CARD ===== --}}
<div id="receipt-print-area" class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    {{-- Header --}}
    <div class="bg-navy px-6 py-6 text-center">
        <h1 class="text-white font-bold text-2xl tracking-wide">
            JABODKA<span class="text-accent">-</span>SIMS
        </h1>
        <p class="text-white/70 text-xs mt-1">Jabodka Outdoor · Davao City</p>
    </div>

    <div class="px-6 py-5">

        {{-- Receipt meta --}}
        <div class="flex justify-between items-start mb-5 pb-4 border-b border-dashed border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Receipt No.</p>
                <p class="text-base font-bold text-ink">{{ $receipt->receipt_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Date</p>
                <p class="text-sm text-ink font-medium">{{ $receipt->issued_at->format('M j, Y') }}</p>
                <p class="text-xs text-gray-400">{{ $receipt->issued_at->format('g:i A') }}</p>
            </div>
        </div>

        {{-- Customer --}}
        @if($receipt->transaction->customer)
        <div class="mb-4 pb-4 border-b border-dashed border-gray-200">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Customer</p>
            <p class="text-sm font-medium text-ink">{{ $receipt->transaction->customer->name ?? 'Walk-in' }}</p>
            @if($receipt->transaction->customer->contact_number)
            <p class="text-xs text-gray-400">{{ $receipt->transaction->customer->contact_number }}</p>
            @endif
        </div>
        @endif

        {{-- Line items --}}
        <div class="mb-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">Items</p>
            <div class="space-y-2">
                @foreach($receipt->transaction->items as $item)
                <div class="flex justify-between items-start gap-2">
                    <div class="min-w-0">
                        <p class="text-sm text-ink leading-tight">{{ $item->variant->displayName() }}</p>
                        <p class="text-xs text-gray-400">
                            ₱{{ number_format($item->price_at_sale, 2) }} × {{ $item->quantity }}
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-ink whitespace-nowrap">
                        ₱{{ number_format($item->price_at_sale * $item->quantity, 2) }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="border-t border-dashed border-gray-200 pt-4 space-y-1.5 mb-5">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal</span>
                <span>₱{{ number_format($receipt->transaction->itemsTotal(), 2) }}</span>
            </div>
            @if($receipt->transaction->shipping_fee > 0)
            <div class="flex justify-between text-sm text-gray-600">
                <span>Shipping ({{ $receipt->transaction->courier ?? 'Courier' }})</span>
                <span>₱{{ number_format($receipt->transaction->shipping_fee, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold text-ink pt-2 border-t border-gray-200">
                <span>TOTAL</span>
                <span>₱{{ number_format($receipt->transaction->grandTotal(), 2) }}</span>
            </div>
        </div>

        {{-- Payment details --}}
        <div class="mb-5 bg-sand rounded-xl px-4 py-3 space-y-1.5">
            @foreach($receipt->transaction->payments->where('payment_status', 'confirmed') as $payment)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 capitalize">{{ $payment->payment_method === 'gcash' ? 'GCash' : 'Cash' }}</span>
                <span class="font-medium">₱{{ number_format($payment->amount_paid, 2) }}</span>
            </div>
            @if($payment->reference_number)
            <p class="text-xs text-gray-400">Ref: {{ $payment->reference_number }}</p>
            @endif
            @endforeach
            @php
                $amountPaid = $receipt->transaction->amountPaid();
                $total = $receipt->transaction->grandTotal();
                $change = $amountPaid - $total;
            @endphp
            @if($change > 0)
            <div class="flex justify-between text-sm border-t border-gray-200 pt-1.5 mt-1.5">
                <span class="text-gray-600">Change</span>
                <span class="font-semibold">₱{{ number_format($change, 2) }}</span>
            </div>
            @endif
        </div>

        {{-- Channel badge --}}
        <div class="text-center mb-4">
            <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full
                {{ $receipt->transaction->channel === 'walk_in' ? 'bg-navy/10 text-navy' : 'bg-accent/15 text-accent-dark' }}">
                {{ $receipt->transaction->channel === 'walk_in' ? 'Walk-In Sale' : 'Online Order' }}
            </span>
            @if($receipt->reprint_count > 0)
            <span class="ml-2 inline-block text-xs font-semibold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700">
                REPRINT #{{ $receipt->reprint_count }}
            </span>
            @endif
        </div>

        {{-- Footer --}}
        <div class="text-center border-t border-dashed border-gray-200 pt-4">
            <p class="text-xs text-gray-500 font-medium">Thank you for your purchase!</p>
            <p class="text-xs text-gray-400 mt-1">Issued by: {{ $receipt->user->username }}</p>
            <p class="text-xs text-gray-400">{{ $receipt->issued_at->format('M j, Y g:i A') }}</p>
        </div>
    </div>
</div>
