<x-layouts.app title="{{ $product->exists ? 'Edit: '.$product->name : 'New Product' }}">

    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Products
        </a>
        <h1 class="text-2xl font-bold text-ink">
            {{ $product->exists ? 'Edit Product' : 'New Product' }}
        </h1>
        @if($product->exists)
        <p class="text-sm text-gray-500 mt-0.5">
            ID #{{ $product->id }}
            @if($product->supplier) · Supplier: <span class="font-medium text-ink">{{ $product->supplier->supplier_name }}</span> @endif
        </p>
        @endif
    </div>

    @include('admin.products._form')

</x-layouts.app>
