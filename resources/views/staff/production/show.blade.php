<x-layouts.app title="Production Entry">

    <div class="mb-6">
        <a href="{{ route('staff.production.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Production Log
        </a>
        <h1 class="text-2xl font-bold text-ink">Production Entry</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $log->production_date->format('l, F j, Y') }}</p>
    </div>

    <div class="max-w-lg space-y-4">

        {{-- Main summary card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-navy/5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-navy rounded-2xl flex flex-col items-center justify-center shrink-0 shadow">
                        <span class="text-2xl font-bold text-white leading-tight">+{{ $log->quantity_produced }}</span>
                        <span class="text-xs text-white/70 leading-tight">produced</span>
                    </div>
                    <div>
                        <p class="font-bold text-ink text-lg">{{ $log->variant->product->name }}</p>
                        @if($log->variant->label())
                        <p class="text-sm text-gray-500">{{ $log->variant->label() }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">
                            Logged by {{ $log->user->username }}
                            · {{ $log->created_at->format('M j, Y g:i A') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($log->notes)
            <div class="px-5 py-4 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Notes</p>
                <p class="text-sm text-gray-700">{{ $log->notes }}</p>
            </div>
            @endif

            {{-- Current stock --}}
            <div class="px-5 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Stock</p>
                    <p class="text-sm text-gray-600 mt-0.5">{{ $log->variant->displayName() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold
                        {{ $log->variant->stock_count === 0 ? 'text-red-600' : ($log->variant->isLowStock() ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $log->variant->stock_count }}
                    </p>
                    <p class="text-xs text-gray-400">in stock now</p>
                </div>
            </div>
        </div>

        {{-- Materials used --}}
        @if($log->materialTransactions->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Raw Materials Used</h2>
                <p class="text-xs text-gray-400 mt-0.5">Deducted from inventory when this entry was submitted</p>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($log->materialTransactions as $tx)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div>
                        <p class="text-sm font-medium text-ink">{{ $tx->material->material_name }}</p>
                        <p class="text-xs text-gray-400">
                            Remaining: {{ number_format($tx->material->stock_quantity, 2) }} {{ $tx->material->unit }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-red-600">
                            −{{ number_format($tx->quantity, 2) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $tx->material->unit }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
            <p class="text-sm text-gray-400 text-center">No raw materials were logged for this entry.</p>
        </div>
        @endif

        {{-- Immutability notice --}}
        <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-xs text-gray-500">
                This entry is <strong>locked</strong> and cannot be edited or deleted.
                Contact the Owner if there's a mistake.
            </p>
        </div>

    </div>

</x-layouts.app>
