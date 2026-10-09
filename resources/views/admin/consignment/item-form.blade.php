<x-layouts.app title="{{ $item->exists ? 'Edit Consignment Item' : 'Add Consignment Item' }}">

    <div class="mb-6">
        <a href="{{ route('admin.consignment.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Consignment
        </a>
        <h1 class="text-2xl font-bold text-ink">
            {{ $item->exists ? 'Edit Consignment Item' : 'Add Consignment Item' }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Record items delivered by a partner on consignment arrangement.
        </p>
    </div>

    @include('admin.consignment._item-form')

</x-layouts.app>
