<x-layouts.app title="{{ $material->exists ? 'Edit Material' : 'Add Raw Material' }}">

    <div class="mb-6">
        <a href="{{ route('staff.materials.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Raw Materials
        </a>
        <h1 class="text-2xl font-bold text-ink">
            {{ $material->exists ? 'Edit Material' : 'Add New Raw Material' }}
        </h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Raw materials are the supplies used when making handmade items.
        </p>
    </div>

    <div class="max-w-sm">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="POST"
                  action="{{ $material->exists
                      ? route('staff.materials.update', $material)
                      : route('staff.materials.store') }}">
                @csrf
                @if($material->exists) @method('PUT') @endif

                {{-- Name --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Material Name <span class="text-red-500">*</span>
                    </label>
                    <input name="material_name" type="text" required
                           value="{{ old('material_name', $material->material_name) }}"
                           placeholder="e.g. Nylon Cord, Canvas Fabric"
                           class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('material_name') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    @error('material_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unit --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Unit of Measure <span class="text-red-500">*</span>
                    </label>
                    <input name="unit" type="text" required
                           value="{{ old('unit', $material->unit) }}"
                           placeholder="e.g. meters, pcs, spools, rolls"
                           list="unit-suggestions"
                           class="w-full h-11 px-4 rounded-xl border-2 {{ $errors->has('unit') ? 'border-red-400' : 'border-gray-200' }} bg-white text-sm focus:outline-none focus:border-navy">
                    <datalist id="unit-suggestions">
                        @foreach(['meters', 'pcs', 'spools', 'rolls', 'kg', 'grams', 'liters', 'pairs'] as $u)
                        <option value="{{ $u }}">
                        @endforeach
                    </datalist>
                    @error('unit')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">How you count or measure this material.</p>
                </div>

                {{-- Opening stock (create only) --}}
                @if(!$material->exists)
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-ink mb-1.5">
                        Current Stock on Hand
                    </label>
                    <div class="flex items-center gap-2">
                        <input name="stock_quantity" type="number" step="0.01" min="0"
                               value="{{ old('stock_quantity', 0) }}"
                               class="flex-1 h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        <span class="text-sm text-gray-500">(units)</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        How much of this material do you have right now?
                        You can update this later using "Receive".
                    </p>
                </div>
                @endif

                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        {{ $material->exists ? 'Save Changes' : 'Add Material' }}
                    </button>
                    <a href="{{ route('staff.materials.index') }}"
                       class="flex-1 h-11 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
