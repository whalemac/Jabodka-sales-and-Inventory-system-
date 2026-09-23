<x-layouts.app title="Add Consignment Item">
    <div class="mb-6">
        <a href="{{ route('admin.consignment.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Consignment
        </a>
        <h1 class="text-2xl font-bold text-ink">Add Consignment Item</h1>
    </div>
    <div class="max-w-lg">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ route('admin.consignment.items.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">Partner <span class="text-red-500">*</span></label>
                    <select name="partner_id" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        <option value="">Select partner…</option>
                        @foreach($partners as $p)<option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->partner_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-ink mb-1.5">Product Variant <span class="text-red-500">*</span></label>
                    <select name="variant_id" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        <option value="">Select variant…</option>
                        @foreach($variants as $v)<option value="{{ $v->id }}" {{ old('variant_id') == $v->id ? 'selected' : '' }}>{{ $v->displayName() }}</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Units Delivered</label>
                        <input name="units_delivered" type="number" min="1" required value="{{ old('units_delivered') }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Received At</label>
                        <input name="received_at" type="date" required value="{{ old('received_at', now()->format('Y-m-d')) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Partner Base Price (₱)</label>
                        <input name="agreed_base_price" type="number" step="0.01" min="0" required value="{{ old('agreed_base_price') }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink mb-1.5">Shop Markup (₱)</label>
                        <input name="shop_markup" type="number" step="0.01" min="0" required value="{{ old('shop_markup', 0) }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">Save</button>
                    <a href="{{ route('admin.consignment.index') }}" class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
