<x-layouts.app title="Production Entry">
    <div class="mb-6">
        <a href="{{ route('staff.production.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Production Log
        </a>
        <h1 class="text-2xl font-bold text-ink">Production Entry</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $log->production_date->format('F j, Y') }}</p>
    </div>
    <div class="max-w-lg space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-navy/10 rounded-2xl flex items-center justify-center shrink-0">
                    <span class="text-xl font-bold text-navy">+{{ $log->quantity_produced }}</span>
                </div>
                <div>
                    <p class="font-bold text-ink">{{ $log->variant->displayName() }}</p>
                    <p class="text-xs text-gray-400">{{ $log->production_date->format('M j, Y') }} · by {{ $log->user->username }}</p>
                </div>
            </div>
            @if($log->notes)
            <p class="text-sm text-gray-600 bg-sand rounded-xl px-4 py-3">{{ $log->notes }}</p>
            @endif
        </div>

        @if($log->materialTransactions->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Materials Used</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($log->materialTransactions as $tx)
                <div class="flex justify-between items-center px-5 py-3.5">
                    <p class="text-sm font-medium text-ink">{{ $tx->material->material_name }}</p>
                    <p class="text-sm text-gray-600">{{ number_format($tx->quantity, 2) }} {{ $tx->material->unit }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <p class="text-center text-xs text-gray-400">This entry is read-only and cannot be edited.</p>
    </div>
</x-layouts.app>
