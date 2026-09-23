<x-layouts.app title="Production Log">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-ink">Production Log</h1>
        <a href="{{ route('staff.production.create') }}" class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">+ Log Production</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($logs->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-400">No production entries yet. <a href="{{ route('staff.production.create') }}" class="text-navy font-medium">Log one →</a></div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($logs as $log)
            <a href="{{ route('staff.production.show', $log) }}" class="flex items-center justify-between px-5 py-4 hover:bg-sand/50 transition">
                <div>
                    <p class="text-sm font-semibold text-ink">{{ $log->variant->displayName() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $log->production_date->format('M j, Y') }}
                        · {{ $log->materialTransactions->count() }} material {{ Str::plural('use', $log->materialTransactions->count()) }}
                    </p>
                </div>
                <div class="text-right ml-4 shrink-0">
                    <span class="inline-block text-sm font-bold px-3 py-1 rounded-full bg-navy/10 text-navy">
                        +{{ $log->quantity_produced }} produced
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @if($logs->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>@endif
        @endif
    </div>
</x-layouts.app>
