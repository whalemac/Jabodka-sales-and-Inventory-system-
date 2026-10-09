    <div class="max-w-lg bg-white rounded-2xl border border-gray-100 p-5 space-y-4">
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-sand rounded-xl p-3">
                <p class="text-xs text-gray-500">Delivered</p>
                <p class="text-lg font-bold text-ink">{{ $item->units_delivered }}</p>
            </div>
            <div class="bg-sand rounded-xl p-3">
                <p class="text-xs text-gray-500">Sold</p>
                <p class="text-lg font-bold text-ink">{{ $item->unitsSoldAllocated() }}</p>
            </div>
            <div class="bg-sand rounded-xl p-3">
                <p class="text-xs text-gray-500">On floor</p>
                <p class="text-lg font-bold text-ink">{{ $item->remainingQuantity() }}</p>
            </div>
        </div>

        @if($item->remainingQuantity() < 1)
            <p class="text-sm text-gray-500">Nothing left on the floor to pull out.</p>
        @else
        <form method="POST" action="{{ route('admin.consignment.items.pullout', $item) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Quantity to pull <span class="text-red-500">*</span></label>
                <input type="number" name="quantity_pulled" min="1" max="{{ $item->remainingQuantity() }}" value="{{ old('quantity_pulled', $item->remainingQuantity()) }}"
                       data-validate="required|integer|positive" data-field="Quantity to pull"
                       class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('quantity_pulled') ? 'border-red-400' : 'border-gray-200' }} text-sm">
                @error('quantity_pulled')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pull-out date</label>
                <input type="date" name="pullout_date" value="{{ old('pullout_date', now()->format('Y-m-d')) }}"
                       data-validate="required|date" data-field="Pull-out date"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reason (optional)</label>
                <textarea name="reason" rows="3" class="w-full px-4 py-2 rounded-xl border-2 border-gray-200 text-sm">{{ old('reason') }}</textarea>
            </div>
            <div class="flex gap-3">
                <button class="flex-1 h-11 bg-navy text-white font-semibold rounded-xl text-sm">Confirm pull-out</button>
                <a href="{{ route('admin.consignment.index') }}" class="flex-1 h-11 flex items-center justify-center bg-gray-100 rounded-xl text-sm">Cancel</a>
            </div>
        </form>
        @endif
    </div>
