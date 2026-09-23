<x-layouts.app title="Returns & Replacements">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Returns & Replacements</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $totalRefunds + $totalReplacements }} total
                · {{ $totalRefunds }} {{ Str::plural('refund', $totalRefunds) }}
                · {{ $totalReplacements }} {{ Str::plural('replacement', $totalReplacements) }}
            </p>
        </div>
        <a href="{{ route('admin.returns.search') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Return
        </a>
    </div>

    @error('duplicate')
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        {{ $message }}
    </div>
    @enderror

    {{-- Filter tabs --}}
    <div class="flex gap-2 mb-4">
        @foreach(['' => 'All', 'refund' => 'Refunds', 'replacement' => 'Replacements'] as $val => $label)
        <a href="{{ route('admin.returns.index', $val ? ['type' => $val] : []) }}"
           class="px-4 py-2 rounded-xl text-sm font-medium transition
                  {{ $typeFilter === $val
                     ? ($val === 'refund' ? 'bg-red-500 text-white shadow-sm' : ($val === 'replacement' ? 'bg-blue-500 text-white shadow-sm' : 'bg-navy text-white shadow-sm'))
                     : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($returns->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                @if($typeFilter)
                    <p class="text-sm text-gray-400">No {{ $typeFilter }}s recorded yet.</p>
                    <a href="{{ route('admin.returns.index') }}" class="mt-2 inline-block text-xs text-navy hover:underline">View all returns</a>
                @else
                    <p class="text-sm text-gray-400 mb-3">No returns recorded yet.</p>
                    <a href="{{ route('admin.returns.search') }}"
                       class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                        Record First Return
                    </a>
                @endif
            </div>
        @else

            {{-- Mobile cards --}}
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($returns as $return)
                <a href="{{ route('admin.returns.show', $return) }}" class="block px-4 py-4 hover:bg-sand/50 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink truncate">
                                {{ $return->salesItem->variant->displayName() }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $return->return_date->format('M j, Y') }}
                                · Tx #{{ $return->salesItem->transaction_id }}
                                @if($return->salesItem->transaction->customer)
                                    · {{ $return->salesItem->transaction->customer->name }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ Str::limit($return->reason, 60) }}</p>
                        </div>
                        <div class="shrink-0 text-right space-y-1">
                            <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ $return->return_type === 'refund' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($return->return_type) }}
                            </span>
                            @if($return->refund_amount)
                            <p class="text-xs font-semibold text-ink">₱{{ number_format($return->refund_amount, 2) }}</p>
                            @endif
                            @if($return->restocked)
                            <p class="text-xs text-green-600 font-medium">✓ Restocked</p>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b border-gray-100 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Date</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Item</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Transaction</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Type</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Reason</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-right">Amount</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Restocked</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($returns as $return)
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 text-xs text-gray-500 whitespace-nowrap">
                                {{ $return->return_date->format('M j, Y') }}
                            </td>
                            <td class="px-5 py-3.5 font-medium text-ink">
                                {{ $return->salesItem->variant->displayName() }}
                                <br><span class="text-xs text-gray-400 font-normal">Qty: {{ $return->salesItem->quantity }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-500">
                                #{{ $return->salesItem->transaction_id }}
                                @if($return->salesItem->transaction->customer)
                                <br>{{ $return->salesItem->transaction->customer->name }}
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full
                                    {{ $return->return_type === 'refund' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($return->return_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600 max-w-xs">
                                {{ Str::limit($return->reason, 60) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-ink">
                                {{ $return->refund_amount ? '₱'.number_format($return->refund_amount, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($return->restocked)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.returns.show', $return) }}"
                                   class="text-xs text-navy font-medium hover:underline">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $returns->links() }}
            </div>
            @endif
        @endif
    </div>

</x-layouts.app>
