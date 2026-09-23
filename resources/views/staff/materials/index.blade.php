<x-layouts.app title="Raw Materials">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Raw Materials</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $materials->count() }} {{ Str::plural('material', $materials->count()) }}
                @if($materials->count() > 0)
                    · Total stock tracked
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('staff.production.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Production Log
            </a>
            <a href="{{ route('staff.materials.create') }}"
               class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Material
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($materials->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-sm text-gray-400 mb-3">No raw materials yet.</p>
                <a href="{{ route('staff.materials.create') }}"
                   class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                    Add First Material
                </a>
            </div>
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($materials as $mat)
                <div class="px-4 py-4" x-data="{ receiving: false, qty: '' }">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">{{ $mat->material_name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Unit: {{ $mat->unit }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xl font-bold
                                {{ $mat->stock_quantity <= 0 ? 'text-red-600' : ($mat->stock_quantity < 5 ? 'text-yellow-600' : 'text-ink') }}">
                                {{ number_format($mat->stock_quantity, 2) }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $mat->unit }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-3">
                        <button @click="receiving = !receiving"
                                class="flex-1 h-9 rounded-xl text-xs font-semibold transition
                                       {{ 'bg-green-600 text-white hover:bg-green-700' }}">
                            + Receive Stock
                        </button>
                        <a href="{{ route('staff.materials.edit', $mat) }}"
                           class="h-9 px-4 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-medium text-ink transition">
                            Edit
                        </a>
                    </div>
                    <div x-show="receiving" x-cloak class="mt-3 bg-green-50 border border-green-200 rounded-xl p-3">
                        <p class="text-xs font-semibold text-green-800 mb-2">How much did you receive?</p>
                        <form method="POST" action="{{ route('staff.materials.receive', $mat) }}" class="flex gap-2">
                            @csrf
                            <div class="flex-1">
                                <input type="number" name="quantity" x-model="qty"
                                       min="0.01" step="0.01" required
                                       placeholder="Enter quantity"
                                       class="w-full h-10 px-3 rounded-lg border-2 border-green-200 text-sm focus:outline-none focus:border-green-500">
                            </div>
                            <span class="flex items-center text-sm text-green-700 font-medium">{{ $mat->unit }}</span>
                            <button type="submit"
                                    class="h-10 px-4 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">
                                Save
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Material Name</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Unit</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Stock on Hand</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Receive Delivery</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($materials as $mat)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-4 font-semibold text-ink">{{ $mat->material_name }}</td>
                            <td class="px-5 py-4 text-gray-500">{{ $mat->unit }}</td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-lg font-bold
                                    {{ $mat->stock_quantity <= 0 ? 'text-red-600' : ($mat->stock_quantity < 5 ? 'text-yellow-600' : 'text-ink') }}">
                                    {{ number_format($mat->stock_quantity, 2) }}
                                </span>
                                <span class="text-xs text-gray-400 ml-1">{{ $mat->unit }}</span>
                                @if($mat->stock_quantity <= 0)
                                <br><span class="inline-block text-xs font-semibold text-red-600 mt-0.5">None left!</span>
                                @elseif($mat->stock_quantity < 5)
                                <br><span class="inline-block text-xs font-semibold text-yellow-600 mt-0.5">Running low</span>
                                @endif
                            </td>
                            <td class="px-5 py-4" x-data="{ open: false, qty: '' }">
                                <button @click="open = !open"
                                        class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-semibold transition"
                                        x-text="open ? 'Cancel' : '+ Receive'">
                                </button>
                                <div x-show="open" x-cloak class="mt-2 flex items-center gap-2">
                                    <form method="POST" action="{{ route('staff.materials.receive', $mat) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        <input type="number" name="quantity" x-model="qty"
                                               min="0.01" step="0.01" required
                                               placeholder="Qty received"
                                               class="w-36 h-9 px-3 rounded-xl border-2 border-gray-200 text-sm focus:outline-none focus:border-navy">
                                        <span class="text-sm text-gray-500">{{ $mat->unit }}</span>
                                        <button type="submit"
                                                class="h-9 px-4 bg-navy hover:bg-navy-dark text-white text-xs font-semibold rounded-xl transition">
                                            Save
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('staff.materials.edit', $mat) }}"
                                   class="text-xs text-navy font-medium hover:underline">Edit</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-layouts.app>
