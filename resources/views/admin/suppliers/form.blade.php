<x-layouts.app title="{{ $supplier->exists ? 'Edit Supplier' : 'Add Supplier' }}">
    <div class="mb-6">
        <a href="{{ route('admin.suppliers.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Suppliers
        </a>
        <h1 class="text-2xl font-bold text-ink">{{ $supplier->exists ? 'Edit Supplier' : 'Add Supplier' }}</h1>
    </div>
    <div class="max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ $supplier->exists ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}">
                @csrf @if($supplier->exists) @method('PUT') @endif
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-ink mb-1.5">Supplier Name <span class="text-red-500">*</span></label>
                    <input name="supplier_name" type="text" required value="{{ old('supplier_name', $supplier->supplier_name) }}"
                           class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('supplier_name') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    @error('supplier_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-ink mb-1.5">Contact Details</label>
                    <textarea name="contact_details" rows="3" placeholder="Phone, email, address…"
                              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 bg-white text-sm resize-none focus:outline-none focus:border-navy">{{ old('contact_details', $supplier->contact_details) }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">Save</button>
                    <a href="{{ route('admin.suppliers.index') }}" class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
