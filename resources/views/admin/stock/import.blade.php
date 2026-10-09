<x-layouts.app title="Add Stock — Import Invoice">

    <div class="mb-6">
        <a href="{{ route('admin.stock.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock
        </a>
        <h1 class="text-2xl font-bold text-ink">Import Products — Supplier Invoice</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            One invoice, many products. Search a variant, add the line, repeat, then save.
        </p>
    </div>

    @include('admin.stock._import')

</x-layouts.app>
