<x-layouts.app title="Adjust Stock — {{ $variant->displayName() }}">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Manual Stock Adjustment</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Use this for corrections, damage, or consignment returns — not for supplier deliveries.
            <a href="{{ route('admin.stock.import-form', ['variant' => $variant->id]) }}"
               class="text-navy font-medium hover:underline">Use Import for deliveries →</a>
        </p>
    </div>

    @include('admin.stock._adjust')

</x-layouts.app>
