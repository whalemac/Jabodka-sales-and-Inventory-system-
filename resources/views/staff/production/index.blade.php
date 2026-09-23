<x-layouts.app title="Production Log">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Production Log</h1>
            <p class="text-sm text-gray-500 mt-0.5">Your finished items history</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('staff.materials.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:border-navy text-ink text-sm font-medium px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h12a2 2 0 012 2v2"/>
                </svg>
                Raw Materials
            </a>
            <a href="{{ route('staff.production.create') }}"
               class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Log Production
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($logs->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <p class="text-sm text-gray-500 mb-4">No production entries yet.</p>
                <a href="{{ route('staff.production.create') }}"
                   class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-navy-dark transition">
                    Log Your First Production
                </a>
            </div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($logs as $log)
                <a href="{{ route('staff.production.show', $log) }}"
                   class="flex items-center gap-4 px-5 py-4 hover:bg-sand/50 transition">

                    {{-- Qty badge --}}
                    <div class="w-14 h-14 rounded-2xl bg-navy/10 flex flex-col items-center justify-center shrink-0">
                        <span class="text-lg font-bold text-navy leading-tight">+{{ $log->quantity_produced }}</span>
                        <span class="text-xs text-navy/70 leading-tight">made</span>
                    </div>

                    {{-- Details --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-ink">{{ $log->variant->product->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $log->variant->label() ?: 'Standard' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $log->production_date->format('M j, Y') }}
                            @if($log->materialTransactions->count() > 0)
                                · {{ $log->materialTransactions->count() }} {{ Str::plural('material', $log->materialTransactions->count()) }} used
                            @endif
                            @if($log->notes)
                                · {{ Str::limit($log->notes, 40) }}
                            @endif
                        </p>
                    </div>

                    {{-- Arrow --}}
                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endforeach
            </div>

            @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
