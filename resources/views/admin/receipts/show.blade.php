<x-layouts.app title="Receipt {{ $receipt->receipt_number }}">

@push('styles')
<style>
    @media print {
        /* Hide everything except the receipt card */
        body > * { display: none !important; }
        #receipt-print-area { display: block !important; }
        #receipt-print-area {
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            font-family: 'Source Sans 3', sans-serif;
        }
    }
</style>
@endpush

{{-- Screen: back + action buttons --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 print:hidden">
    <a href="{{ route('admin.walk-in.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Walk-In Orders
    </a>
    <div class="flex gap-2 flex-wrap">
        {{-- Reprint --}}
        <form method="POST" action="{{ route('admin.receipts.reprint', $receipt) }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Mark Reprint
            </button>
        </form>
        {{-- Browser print --}}
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Receipt
        </button>
    </div>
</div>

{{-- Reprint badge --}}
@if($receipt->reprint_count > 0)
<div class="mb-4 inline-flex items-center gap-2 bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs font-semibold px-3 py-1.5 rounded-lg print:hidden">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    Reprinted {{ $receipt->reprint_count }}× — Last: {{ $receipt->last_printed_at?->format('M j, Y g:i A') }}
</div>
@endif

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

{{-- Screen: transaction detail link --}}
<div class="max-w-md mx-auto mt-4 print:hidden">
    <a href="{{ route('admin.walk-in.index') }}"
       class="block text-center text-sm text-gray-500 hover:text-navy">
        ← Back to all walk-in orders
    </a>
</div>

</x-layouts.app>
