<x-layouts.app title="Online Orders">

    @php
        $shipmentLabels = \App\Models\SalesTransaction::SHIPMENT_STATUSES;
        $paymentLabels = \App\Models\SalesTransaction::PAYMENT_STATUSES;
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

    <div x-data="orderQuickEdit({
             shipmentUrl: @js(route('admin.online.shipment', ['transaction' => '__ID__'])),
             paymentUrl: @js(route('admin.online.payment-status', ['transaction' => '__ID__'])),
             payColors: @js($payColors),
             shipColors: @js($shipColors),
             payLabels: @js($paymentLabels),
             shipLabels: @js($shipmentLabels),
         })">
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
                @php $pay = $order->derivedPaymentStatus(); @endphp
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
                            <span class="inline-flex items-center justify-center w-full text-xs font-semibold px-2.5 py-1.5 rounded-full border text-center"
                                  :class="payClass('p' + {{ $order->id }}, '{{ $pay }}')"
                                  x-text="payLabel('p' + {{ $order->id }}, '{{ $pay }}')"></span>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-1">Shipment</p>
                            <span class="inline-flex items-center justify-center w-full text-xs font-semibold px-2.5 py-1.5 rounded-full border text-center"
                                  :class="shipClass('s' + {{ $order->id }}, '{{ $order->shipment_status }}')"
                                  x-text="shipLabel('s' + {{ $order->id }}, '{{ $order->shipment_status }}')"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <a href="{{ route('admin.online.show', $order) }}" class="text-xs text-navy font-semibold hover:underline">View full order →</a>
                        <button type="button" @click="openEdit({{ $order->id }}, '{{ $pay }}', '{{ $order->shipment_status }}')"
                                class="text-xs text-navy font-semibold hover:underline">Edit status</button>
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
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Tracking</th>
                            <th class="px-5 py-3 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orders as $order)
                        @php $pay = $order->derivedPaymentStatus(); @endphp
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
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border"
                                      :class="payClass('p' + {{ $order->id }}, '{{ $pay }}')"
                                      x-text="payLabel('p' + {{ $order->id }}, '{{ $pay }}')"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full border"
                                      :class="shipClass('s' + {{ $order->id }}, '{{ $order->shipment_status }}')"
                                      x-text="shipLabel('s' + {{ $order->id }}, '{{ $order->shipment_status }}')"></span>
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
                                            @click="openEdit({{ $order->id }}, '{{ $pay }}', '{{ $order->shipment_status }}')"
                                            class="inline-flex items-center gap-1 text-xs text-navy font-semibold hover:underline">
                                        Edit Status
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

    {{-- Edit status modal --}}
    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="editing = null"></div>
        <div x-show="editing" x-transition.opacity.duration.150ms
             class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-lg font-bold text-ink">Edit Order Status</h2>
            <p class="text-xs text-gray-400 mt-0.5">Order #<span x-text="editing"></span> — changes are saved when you confirm.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Status</label>
                    <select x-model="editPayment"
                            class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        @foreach($paymentLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Shipment Status</label>
                    <select x-model="editShipment"
                            class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                        @foreach($shipmentLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <p x-show="saveError" x-cloak class="text-xs text-red-600 mt-4" x-text="saveError"></p>

            <div class="flex gap-3 mt-6">
                <button type="button" @click="saveStatuses()" :disabled="saving"
                        class="flex-1 h-11 inline-flex items-center justify-center gap-2 bg-navy hover:bg-navy-dark disabled:opacity-60 text-white text-sm font-semibold rounded-xl transition">
                    <span x-show="saving" x-cloak class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    Save Changes
                </button>
                <button type="button" @click="editing = null"
                        class="flex-1 h-11 bg-gray-100 hover:bg-gray-200 text-ink text-sm font-medium rounded-xl transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    </div>

@push('scripts')
<script>
function orderQuickEdit({ shipmentUrl, paymentUrl, payColors, shipColors, payLabels, shipLabels }) {
    return {
        overrides: {},
        editing: null,
        editPayment: '',
        editShipment: '',
        saving: false,
        saveError: '',
        csrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },
        async patch(url, body) {
            try {
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
                return res.ok ? payload : null;
            } catch (e) {
                return null;
            }
        },
        openEdit(id, payment, shipment) {
            this.saveError = '';
            this.editing = id;
            this.editPayment = payment;
            this.editShipment = shipment;
        },
        async saveStatuses() {
            const id = this.editing;
            const wantedPayment = this.editPayment;
            const wantedShipment = this.editShipment;

            const message = 'Update this order to:\n'
                + '· Payment: ' + (payLabels[wantedPayment] || wantedPayment) + '\n'
                + '· Shipment: ' + (shipLabels[wantedShipment] || wantedShipment);
            if (!confirm(message)) return;

            this.saving = true;
            this.saveError = '';

            try {
                const [payRes, shipRes] = await Promise.all([
                    this.patch(paymentUrl.replace('__ID__', id), { payment_status: wantedPayment }),
                    this.patch(shipmentUrl.replace('__ID__', id), { shipment_status: wantedShipment }),
                ]);

                if (!payRes || !shipRes) {
                    this.saveError = 'Could not save the status changes. Check your connection and try again.';
                    return;
                }

                this.overrides['p' + id] = payRes.payment_status || wantedPayment;
                this.overrides['s' + id] = shipRes.shipment_status || wantedShipment;
                this.editing = null;
            } finally {
                this.saving = false;
            }
        },
        payClass(key, fallback) {
            return payColors[this.overrides[key] || fallback] || '';
        },
        shipClass(key, fallback) {
            return shipColors[this.overrides[key] || fallback] || '';
        },
        payLabel(key, fallback) {
            const value = this.overrides[key] || fallback;
            return payLabels[value] || fallback;
        },
        shipLabel(key, fallback) {
            const value = this.overrides[key] || fallback;
            return shipLabels[value] || fallback;
        },
    };
}
</script>
@endpush

</x-layouts.app>
