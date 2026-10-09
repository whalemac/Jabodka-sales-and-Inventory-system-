<x-layouts.app title="Online Orders">

    @php
        $shipmentLabels = \App\Models\SalesTransaction::SHIPMENT_STATUSES;
        $paymentLabels = \App\Models\SalesTransaction::PAYMENT_STATUSES;
        $payNext = \App\Models\SalesTransaction::PAYMENT_TRANSITIONS;
        $shipNext = \App\Models\SalesTransaction::SHIPMENT_TRANSITIONS;
        $payColors = [
            'pending' => 'bg-gray-100 text-gray-700 border-gray-200',
            'confirmed' => 'bg-green-100 text-green-700 border-green-200',
            'refunded' => 'bg-red-100 text-red-700 border-red-200',
        ];
        $shipColors = [
            'pending' => 'bg-gray-100 text-gray-600 border-gray-200',
            'preparing' => 'bg-sky-100 text-sky-800 border-sky-200',
            'shipped' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'in_transit' => 'bg-purple-100 text-purple-800 border-purple-200',
            'delivered' => 'bg-green-100 text-green-800 border-green-200',
            'returned' => 'bg-orange-100 text-orange-800 border-orange-200',
        ];
        $shipChunks = [
            'pending' => ['pct' => 10,  'bar' => 'bg-gray-300'],
            'preparing' => ['pct' => 40,  'bar' => 'bg-sky-500'],
            'shipped' => ['pct' => 55,  'bar' => 'bg-indigo-500'],
            'in_transit' => ['pct' => 75, 'bar' => 'bg-purple-500'],
            'delivered' => ['pct' => 100, 'bar' => 'bg-green-500'],
            'returned' => ['pct' => 100, 'bar' => 'bg-orange-500'],
        ];
        $progressOf = function ($order) use ($shipChunks) {
            $chunk = $shipChunks[$order->shipment_status] ?? ['pct' => 10, 'bar' => 'bg-gray-300'];
            return $chunk + ['label' => \App\Models\SalesTransaction::SHIPMENT_STATUSES[$order->shipment_status] ?? $order->shipment_status];
        };
        $current = request('status', '');
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">Online Orders</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $orders->total() }} total orders</p>
        </div>
        <a href="{{ route('admin.online.create') }}"
           class="inline-flex items-center gap-2 bg-navy hover:bg-navy-dark text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Online Order
        </a>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.online.index') }}"
           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $current === '' ? 'bg-navy text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
            All
        </a>
        @foreach($shipmentLabels as $val => $label)
        <a href="{{ route('admin.online.index', ['status' => $val]) }}"
           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $current === $val ? 'bg-navy text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($orders->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm text-gray-400 mb-4">No online orders {{ $current ? 'with this status' : 'yet' }}.</p>
                @if(!$current)
                <a href="{{ route('admin.online.create') }}"
                   class="inline-flex items-center gap-2 bg-navy text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-navy-dark transition">
                    Create First Order
                </a>
                @endif
            </div>
        @else
            <div class="divide-y divide-gray-50 sm:hidden">
                @foreach($orders as $order)
                @php $pay = $order->derivedPaymentStatus(); $prog = $progressOf($order); @endphp
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink truncate">{{ $order->customer?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $order->transaction_date->format('M j, Y · g:i A') }}</p>
                        </div>
                        <p class="text-sm font-bold text-ink">₱{{ number_format($order->grandTotal(), 2) }}</p>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Payment</p>
                            <span class="inline-flex items-center justify-center w-full text-xs font-semibold px-2.5 py-1.5 rounded-full border text-center whitespace-nowrap {{ $payColors[$pay] ?? '' }}">{{ $paymentLabels[$pay] ?? $pay }}</span>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Shipment</p>
                            <span class="inline-flex items-center justify-center w-full text-xs font-semibold px-2.5 py-1.5 rounded-full border text-center whitespace-nowrap {{ $shipColors[$order->shipment_status] ?? '' }}">{{ $shipmentLabels[$order->shipment_status] ?? $order->shipment_status }}</span>
                            <div class="mt-2 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $prog['bar'] }}" style="width: {{ $prog['pct'] }}%"></div>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-400">{{ $shipmentLabels[$order->shipment_status] ?? $order->shipment_status }} · {{ $prog['pct'] }}%</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <a href="{{ route('admin.online.show', $order) }}" class="text-xs text-navy font-semibold hover:underline">View full order →</a>
                        <button type="button" onclick="openStatusModal(this)"
                                 data-id="{{ $order->id }}"
                                 data-pay="{{ $pay }}"
                                 data-ship="{{ $order->shipment_status }}"
                                 data-tracking="{{ $order->tracking_number }}"
                                 data-courier="{{ $order->courier }}"
                                 class="text-xs text-navy font-semibold hover:underline">Update status</button>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-sand border-b-2 border-gray-200">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Customer</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Items</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Total</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Payment Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Shipment Status</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Progress</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Tracking</th>
                            <th class="px-5 py-3 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orders as $order)
                        @php $pay = $order->derivedPaymentStatus(); $prog = $progressOf($order); @endphp
                        <tr class="hover:bg-sand/50 transition">
                            <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap text-xs">
                                {{ $order->transaction_date->format('M j, Y') }}<br>
                                <span class="text-gray-400">{{ $order->transaction_date->format('g:i A') }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-ink">{{ $order->customer?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $order->customer?->contact_number }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</td>
                            <td class="px-5 py-3.5 font-semibold text-ink">₱{{ number_format($order->grandTotal(), 2) }}</td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border whitespace-nowrap {{ $payColors[$pay] ?? '' }}">{{ $paymentLabels[$pay] ?? $pay }}</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border whitespace-nowrap {{ $shipColors[$order->shipment_status] ?? '' }}">{{ $shipmentLabels[$order->shipment_status] ?? $order->shipment_status }}</span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                        <div class="h-full rounded-full {{ $prog['bar'] }}" style="width: {{ $prog['pct'] }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-semibold text-gray-400">{{ $prog['pct'] }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-500">
                                {{ $order->tracking_number ?? '—' }}
                                @if($order->courier)<br><span class="text-gray-400">{{ $order->courier }}</span>@endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-4">
                                    <a href="{{ route('admin.online.show', $order) }}"
                                       class="inline-flex items-center gap-1 text-xs text-gray-500 font-medium hover:text-navy hover:underline">
                                        View
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                            onclick="openStatusModal(this)"
                                            data-id="{{ $order->id }}"
                                            data-pay="{{ $pay }}"
                                            data-ship="{{ $order->shipment_status }}"
                                            data-tracking="{{ $order->tracking_number }}"
                                            data-courier="{{ $order->courier }}"
                                            class="inline-flex items-center gap-1 text-xs text-navy font-semibold hover:underline">
                                        Update Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Update status modal: forward-only, tracking auto-ships, reference auto-confirms --}}
    <x-modal name="status-modal" title="Update Order Status" subtitle="Each action saves immediately. Statuses only move forward, never back." size="md">
        <div x-data="statusModal({
                 shipmentUrl: @js(route('admin.online.shipment', ['transaction' => '__ID__'])),
                 paymentUrl: @js(route('admin.online.payment-status', ['transaction' => '__ID__'])),
                 payColors: @js($payColors),
                 shipColors: @js($shipColors),
                 payLabels: @js($paymentLabels),
                 shipLabels: @js($shipmentLabels),
                 payNext: @js($payNext),
                 shipNext: @js($shipNext),
             })"
             @open-status.window="open($event.detail)">
            <p class="text-xs text-gray-500">Order #<span class="font-bold text-ink" x-text="editing ?? '—'"></span></p>

            <div class="mt-5 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Payment Status</label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border whitespace-nowrap"
                              :class="payColors[editPaymentValue] || ''"
                              x-text="payLabels[editPaymentValue] || editPaymentValue">Pending</span>
                        <template x-for="next in payNext[editPaymentValue] || []" :key="next">
                            <button type="button"
                                    @click="savePayment(next)"
                                    :disabled="saving"
                                    class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full border border-navy text-navy hover:bg-navy hover:text-white transition disabled:opacity-50">
                                <span x-text="payLabels[next] || next"></span> →
                            </button>
                        </template>
                        <template x-if="!(payNext[editPaymentValue] || []).length">
                            <span class="text-xs text-gray-400">No further payment steps.</span>
                        </template>
                    </div>
                    <div class="mt-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Payment reference # <span class="text-gray-400 font-normal">(entering one auto-confirms payment)</span></label>
                        <div class="flex gap-2">
                            <input type="text" x-model="payReference"
                                   placeholder="e.g. GCash ref 09123456789"
                                   class="flex-1 h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                            <button type="button" @click="savePaymentReference()" :disabled="saving || !payReference"
                                    class="h-10 px-3 bg-navy text-white text-xs font-semibold rounded-xl hover:bg-navy-dark transition disabled:opacity-50 shrink-0">
                                Save ref
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Shipment Status</label>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border whitespace-nowrap"
                              :class="shipColors[editShipmentValue] || ''"
                              x-text="shipLabels[editShipmentValue] || editShipmentValue">Pending</span>
                        <template x-for="next in shipNext[editShipmentValue] || []" :key="next">
                            <button type="button"
                                    @click="saveShipment(next)"
                                    :disabled="saving || editPaymentValue === 'pending'"
                                    class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full border border-navy text-navy hover:bg-navy hover:text-white transition disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-text="shipLabels[next] || next"></span> →
                            </button>
                        </template>
                        <template x-if="!(shipNext[editShipmentValue] || []).length">
                            <span class="text-xs text-gray-400">No further shipment steps.</span>
                        </template>
                    </div>
                    <p x-show="editPaymentValue === 'pending'" x-cloak
                       class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Payment is still Pending — confirm payment first to unlock shipment updates.
                    </p>
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tracking # & Courier <span class="text-gray-400 font-normal">(entering tracking auto-marks as Shipped)</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" x-model="tracking"
                               placeholder="e.g. JT123456789"
                               class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        <input type="text" x-model="courier"
                               placeholder="Courier (optional)"
                               class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                    </div>
                    <button type="button" @click="saveTracking()" :disabled="saving"
                            class="text-xs font-semibold text-navy hover:underline disabled:opacity-50">
                        Save tracking only
                    </button>
                </div>
            </div>

            <p x-show="saveError" x-cloak class="text-xs text-red-600 mt-4" x-text="saveError"></p>

            <div class="flex gap-3 mt-6">
                <button type="button" @click="close()"
                        class="flex-1 h-11 bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                    Done
                </button>
            </div>
        </div>
    </x-modal>

    </div>

@push('scripts')
<script>
// Plain-JS opener (no Alpine dependency): the button carries the order's
// current statuses in data-* attributes, filled server-side.
function openStatusModal(btn) {
    const d = btn.dataset || {};
    window.dispatchEvent(new CustomEvent('open-status', {
        detail: {
            id: d.id,
            pay: d.pay || 'pending',
            ship: d.ship || 'pending',
            tracking: d.tracking || '',
            courier: d.courier || '',
        },
    }));
}
window.openStatusModal = openStatusModal;

// Self-contained status modal. Only forward (next-step) moves are offered —
// the server also rejects any backward move. After each save the list
// reloads so the badges always show the server truth.
function statusModal({ shipmentUrl, paymentUrl, payColors, shipColors, payLabels, shipLabels, payNext, shipNext }) {
    return {
        payColors, shipColors, payLabels, shipLabels, payNext, shipNext,
        editing: null,
        editPaymentValue: 'pending',
        editShipmentValue: 'pending',
        tracking: '',
        courier: '',
        payReference: '',
        saving: false,
        saveError: '',
        open(detail) {
            this.saveError = '';
            this.saving = false;
            this.editing = detail.id || null;
            this.editPaymentValue = detail.pay || 'pending';
            this.editShipmentValue = detail.ship || 'pending';
            this.tracking = detail.tracking || '';
            this.courier = detail.courier || '';
            this.payReference = '';
            if (window.Jabodka) window.Jabodka.modalOpen('status-modal');
        },
        close() {
            if (window.Jabodka) window.Jabodka.modalClose('status-modal');
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },
        async patch(url, body) {
            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const payload = await res.json().catch(() => ({}));
            if (!res.ok) {
                const msg = (payload.errors && Object.values(payload.errors)[0]) || payload.message || 'Could not save.';
                throw new Error(Array.isArray(msg) ? msg[0] : msg);
            }
            return payload;
        },
        saved(message) {
            window.Jabodka?.toast(message, 'success');
            setTimeout(() => window.location.reload(), 600);
        },
        async savePayment(next) {
            if (!this.editing) return;
            this.saving = true;
            this.saveError = '';
            try {
                const res = await this.patch(paymentUrl.replace('__ID__', this.editing), {
                    payment_status: next,
                    ...(this.payReference ? { reference_number: this.payReference } : {}),
                });
                this.editPaymentValue = res.payment_status || next;
                this.payReference = '';
                this.saved('Payment status updated.');
            } catch (e) {
                this.saveError = e.message;
            } finally {
                this.saving = false;
            }
        },
        async savePaymentReference() {
            if (!this.editing || !this.payReference) return;
            this.saving = true;
            this.saveError = '';
            try {
                // Reference alone auto-confirms a pending payment (server-side).
                const res = await this.patch(paymentUrl.replace('__ID__', this.editing), {
                    payment_status: this.editPaymentValue,
                    reference_number: this.payReference,
                });
                this.editPaymentValue = res.payment_status || 'confirmed';
                this.payReference = '';
                this.saved('Payment reference saved — payment confirmed.');
            } catch (e) {
                this.saveError = e.message;
            } finally {
                this.saving = false;
            }
        },
        async saveShipment(next) {
            if (!this.editing) return;
            this.saving = true;
            this.saveError = '';
            try {
                const res = await this.patch(shipmentUrl.replace('__ID__', this.editing), {
                    shipment_status: next,
                    ...(this.tracking ? { tracking_number: this.tracking } : {}),
                    ...(this.courier ? { courier: this.courier } : {}),
                });
                this.editShipmentValue = res.shipment_status || next;
                this.saved('Shipment status updated.');
            } catch (e) {
                this.saveError = e.message;
            } finally {
                this.saving = false;
            }
        },
        async saveTracking() {
            if (!this.editing) return;
            this.saving = true;
            this.saveError = '';
            try {
                // Tracking alone auto-marks Pending/Preparing as Shipped (server-side).
                const res = await this.patch(shipmentUrl.replace('__ID__', this.editing), {
                    shipment_status: this.editShipmentValue,
                    ...(this.tracking ? { tracking_number: this.tracking } : {}),
                    ...(this.courier ? { courier: this.courier } : {}),
                });
                if (res.shipment_status) this.editShipmentValue = res.shipment_status;
                this.saved('Tracking saved.');
            } catch (e) {
                this.saveError = e.message;
            } finally {
                this.saving = false;
            }
        },
    };
}
window.statusModal = statusModal;
</script>
@endpush

</x-layouts.app>