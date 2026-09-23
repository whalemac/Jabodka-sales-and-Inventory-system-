<x-layouts.app title="Consignment Payouts">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-ink">Consignment Payouts</h1>
        <a href="{{ route('admin.consignment.index') }}" class="text-sm text-gray-500 hover:text-navy">← Consignment</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- Generate payout form --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-ink text-sm mb-4">Generate New Payout</h2>
                <form method="POST" action="{{ route('admin.consignment.payouts.generate') }}" class="space-y-3">
                    @csrf
                    @error('partner_id')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Partner</label>
                        <select name="partner_id" required class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <option value="">Select partner…</option>
                            @foreach($partners as $p)<option value="{{ $p->id }}">{{ $p->partner_name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Period Start</label>
                        <input type="date" name="period_start" required value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Period End</label>
                        <input type="date" name="period_end" required value="{{ now()->format('Y-m-d') }}"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <button type="submit" class="w-full h-11 bg-navy hover:bg-navy-dark text-white font-semibold text-sm rounded-xl transition">
                        Generate Payout
                    </button>
                </form>

                {{-- Unsettled items summary --}}
                @if($unsettled->isNotEmpty())
                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-600 mb-2">Unsettled Sales by Partner</p>
                    @foreach($unsettled as $partnerId => $items)
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700">{{ $items->first()->variant->consignmentItems->first()?->partner?->partner_name ?? 'Unknown' }}</span>
                        <span class="font-semibold text-ink">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Payout history --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-ink text-sm">Payout History</h2>
                </div>
                @if($payments->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400">No payouts generated yet.</div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($payments as $payout)
                    <div class="px-5 py-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink text-sm">{{ $payout->partner->partner_name }}</p>
                            <p class="text-xs text-gray-400">{{ $payout->period_start->format('M j') }} – {{ $payout->period_end->format('M j, Y') }}</p>
                            @if($payout->reference_number)
                            <p class="text-xs text-gray-400">Ref: {{ $payout->reference_number }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-ink">₱{{ number_format($payout->total_amount, 2) }}</p>
                            @if($payout->payment_status === 'paid')
                                <span class="inline-block text-xs px-2.5 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold">Paid</span>
                            @else
                                <form method="POST" action="{{ route('admin.consignment.payouts.mark-paid', $payout) }}" x-data="{ ref: '' }" class="mt-1">
                                    @csrf @method('PATCH')
                                    <div class="flex gap-1 mt-1">
                                        <input type="text" name="reference_number" x-model="ref" placeholder="GCash ref"
                                               class="w-28 h-7 px-2 rounded-lg border border-gray-200 text-xs focus:outline-none focus:border-navy">
                                        <button type="submit" class="h-7 px-3 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition">
                                            Mark Paid
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($payments->hasPages())<div class="px-5 py-4 border-t border-gray-100">{{ $payments->links() }}</div>@endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
