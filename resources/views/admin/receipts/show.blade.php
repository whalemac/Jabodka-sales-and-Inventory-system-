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

@include('admin.receipts._fragment', ['receipt' => $receipt])


{{-- Screen: transaction detail link --}}
<div class="max-w-md mx-auto mt-4 print:hidden">
    <a href="{{ route('admin.walk-in.index') }}"
       class="block text-center text-sm text-gray-500 hover:text-navy">
        ← Back to all walk-in orders
    </a>
</div>

</x-layouts.app>
