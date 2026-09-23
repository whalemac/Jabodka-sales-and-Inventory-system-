<x-layouts.app title="New Online Order">

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('admin.online.index') }}" class="text-gray-500 hover:text-navy">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-ink">New Online Order</h1>
</div>

{{-- Server errors from redirect back --}}
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
    {{ $errors->first() }}
</div>
@endif

<div x-data="onlineOrder()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- ===== LEFT: Product search + cart ===== --}}
    <div class="lg:col-span-3 space-y-4">

        {{-- Search --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <label class="block text-sm font-semibold text-ink mb-2">Search Products</label>
            <div class="relative">
                <input type="text"
                       x-model="query"
                       @input.debounce.300ms="search()"
                       @keydown.escape="results = []"
                       placeholder="Type product name, size, or version…"
                       class="w-full h-12 pl-10 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy"
                       autocomplete="off">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Search results --}}
            <div x-show="results.length > 0" x-cloak
                 class="mt-2 border border-gray-100 rounded-xl overflow-hidden shadow-lg">
                <template x-for="item in results" :key="item.id">
                    <button type="button"
                            @click="addToCart(item)"
                            :disabled="item.stock === 0"
                            class="w-full flex items-center justify-between px-4 py-3 hover:bg-sand text-left border-b border-gray-50 last:border-0 transition"
                            :class="item.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate" x-text="item.name"></p>
                            <p class="text-xs mt-0.5"
                               :class="item.stock === 0 ? 'text-red-500 font-semibold' : (item.low ? 'text-yellow-600 font-semibold' : 'text-gray-400')">
                                <span x-text="item.stock === 0 ? 'Out of stock' : (item.low ? '⚠ Low stock — ' + item.stock + ' left' : item.stock + ' in stock')"></span>
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-navy ml-4 shrink-0"
                           x-text="'₱' + item.price.toFixed(2)"></p>
                    </button>
                </template>
            </div>
            <p x-show="query.length >= 2 && results.length === 0 && !searching" x-cloak
               class="mt-2 text-sm text-gray-400 px-1">No products found.</p>
        </div>

        {{-- Cart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Order Items</h2>
                <span class="text-xs text-gray-400" x-text="cart.length + ' item(s)'"></span>
            </div>

            <div x-show="cart.length === 0" class="px-5 py-10 text-center">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
                <p class="text-sm text-gray-400">Search above and tap a product to add it.</p>
            </div>

            <div x-show="cart.length > 0" class="divide-y divide-gray-50">
                <template x-for="(line, idx) in cart" :key="line.variantId">
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink truncate" x-text="line.name"></p>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="'₱' + line.price.toFixed(2) + ' each'"></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="decrement(idx)"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold text-gray-600">−</button>
                            <input type="number" x-model.number="line.qty" @change="clampQty(idx)"
                                   min="1" :max="line.maxStock"
                                   class="w-14 h-8 text-center rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-navy">
                            <button type="button"
                                    @click="increment(idx)"
                                    :disabled="line.qty >= line.maxStock"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold text-gray-600 disabled:opacity-40">+</button>
                        </div>
                        <p class="w-20 text-right text-sm font-semibold text-ink shrink-0"
                           x-text="'₱' + (line.price * line.qty).toFixed(2)"></p>
                        <button type="button" @click="remove(idx)"
                                class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center text-gray-400 hover:text-red-500 transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 bg-sand/50 space-y-1.5">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span>
                    <span x-text="'₱' + cartTotal().toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Shipping</span>
                    <span x-text="'₱' + parseFloat(shippingFee || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between font-bold text-base text-ink pt-2 border-t border-gray-200">
                    <span>Grand Total</span>
                    <span x-text="'₱' + grandTotal().toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: Customer + Shipping + Payment ===== --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Customer & Shipping --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-ink text-sm">Customer & Shipping</h2>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="customerName" required
                       placeholder="Facebook name or full name"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy"
                       :class="validationErrors.customerName ? 'border-red-400' : ''">
                <p x-show="validationErrors.customerName" x-cloak x-text="validationErrors.customerName"
                   class="text-xs text-red-600 mt-1"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Contact Number <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="contactNumber" required
                       placeholder="09xx-xxx-xxxx"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy"
                       :class="validationErrors.contactNumber ? 'border-red-400' : ''">
                <p x-show="validationErrors.contactNumber" x-cloak x-text="validationErrors.contactNumber"
                   class="text-xs text-red-600 mt-1"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Shipping Address <span class="text-red-500">*</span>
                </label>
                <textarea x-model="shippingAddress" required rows="2"
                          placeholder="Street, Barangay, City"
                          class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-sm resize-none focus:outline-none focus:border-navy"
                          :class="validationErrors.shippingAddress ? 'border-red-400' : ''"></textarea>
                <p x-show="validationErrors.shippingAddress" x-cloak x-text="validationErrors.shippingAddress"
                   class="text-xs text-red-600 mt-1"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Landmark</label>
                <input type="text" x-model="landmark"
                       placeholder="Nearby landmark (optional)"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Courier</label>
                    <input type="text" x-model="courier" placeholder="J&T Express"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Shipping Fee (₱)</label>
                    <input type="number" step="0.01" min="0" x-model.number="shippingFee"
                           placeholder="0.00"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
            </div>
        </div>

        {{-- Payment (optional at order time) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3"
             x-data="{ method: '' }">
            <div class="flex items-start justify-between">
                <h2 class="font-semibold text-ink text-sm">Payment <span class="text-gray-400 font-normal">(optional)</span></h2>
                <span class="text-xs text-gray-400 leading-tight text-right">You can confirm<br>payment later</span>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="method === 'cash' ? method = '' : method = 'cash'; paymentMethod = method"
                        :class="method === 'cash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                        class="h-10 rounded-xl border-2 text-xs font-semibold transition">Cash</button>
                <button type="button" @click="method === 'gcash' ? method = '' : method = 'gcash'; paymentMethod = method"
                        :class="method === 'gcash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                        class="h-10 rounded-xl border-2 text-xs font-semibold transition">GCash</button>
                <button type="button" @click="method = ''; paymentMethod = ''"
                        :class="method === '' ? 'bg-gray-100 text-gray-700 border-gray-300' : 'bg-white text-gray-400 border-gray-200 hover:border-gray-300'"
                        class="h-10 rounded-xl border-2 text-xs font-medium transition">Skip</button>
            </div>

            <div x-show="method !== ''" x-cloak class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Amount Paid (₱)</label>
                    <input type="number" step="0.01" min="0" x-model.number="amountPaid"
                           :placeholder="grandTotal().toFixed(2)"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>

                <div x-show="method === 'gcash'" x-cloak>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">GCash Reference # <span class="text-red-500">*</span></label>
                    <input type="text" x-model="paymentReference"
                           placeholder="e.g. 09123456789"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>

                <label x-show="amountPaid > 0" x-cloak class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="confirmPayment"
                           class="w-4 h-4 rounded border-gray-300 text-navy focus:ring-navy">
                    <span class="text-sm text-gray-700">Mark as <strong>confirmed</strong> (auto-advance to Ready to Ship)</span>
                </label>
            </div>
        </div>

        {{-- Submit --}}
        <div>
            <p x-show="submitError" x-cloak x-text="submitError"
               class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-3"></p>

            <form id="online-form" method="POST" action="{{ route('admin.online.store') }}">
                @csrf
                <div id="online-fields"></div>
            </form>

            <button type="button"
                    @click="submit()"
                    :disabled="cart.length === 0 || submitting"
                    class="w-full h-14 bg-navy hover:bg-navy-dark text-white font-bold text-base rounded-2xl
                           transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-md">
                <svg x-show="submitting" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span x-text="submitting ? 'Saving…' : 'Save Online Order'"></span>
            </button>
            <p class="text-center text-xs text-gray-400 mt-2">
                Order starts as "Awaiting Payment" unless payment is confirmed above.
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function onlineOrder() {
    return {
        // Search state
        query: '', results: [], searching: false,
        // Cart
        cart: [],
        // Customer
        customerName: '', contactNumber: '', shippingAddress: '', landmark: '',
        // Shipping
        courier: 'J&T Express', shippingFee: 0,
        // Payment
        paymentMethod: '', amountPaid: 0, paymentReference: '', confirmPayment: false,
        // UI
        submitting: false, submitError: '', validationErrors: {},

        init() {
            this.$nextTick(() => {
                document.querySelector('input[placeholder*="Type product"]')?.focus();
            });
        },

        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            this.searching = true;
            try {
                const res = await fetch(
                    `{{ route('admin.walk-in.search') }}?q=` + encodeURIComponent(this.query),
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                this.results = await res.json();
            } catch (e) {
                this.results = [];
            } finally {
                this.searching = false;
            }
        },

        addToCart(item) {
            if (item.stock === 0) return;
            const existing = this.cart.find(l => l.variantId === item.id);
            if (existing) {
                if (existing.qty < existing.maxStock) existing.qty++;
            } else {
                this.cart.push({
                    variantId: item.id, name: item.name,
                    price: item.price, qty: 1, maxStock: item.stock,
                });
            }
            this.query = '';
            this.results = [];
        },

        increment(idx) {
            const l = this.cart[idx];
            if (l.qty < l.maxStock) l.qty++;
        },
        decrement(idx) {
            if (this.cart[idx].qty > 1) this.cart[idx].qty--;
            else this.remove(idx);
        },
        clampQty(idx) {
            const l = this.cart[idx];
            l.qty = Math.max(1, Math.min(l.maxStock, parseInt(l.qty) || 1));
        },
        remove(idx) { this.cart.splice(idx, 1); },

        cartTotal() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
        grandTotal() { return this.cartTotal() + parseFloat(this.shippingFee || 0); },

        validate() {
            this.validationErrors = {};
            if (!this.cart.length)              { this.submitError = 'Add at least one item.'; return false; }
            if (!this.customerName.trim())       { this.validationErrors.customerName = 'Required'; this.submitError = 'Fill in required customer fields.'; return false; }
            if (!this.contactNumber.trim())      { this.validationErrors.contactNumber = 'Required'; this.submitError = 'Fill in required customer fields.'; return false; }
            if (!this.shippingAddress.trim())    { this.validationErrors.shippingAddress = 'Required'; this.submitError = 'Fill in required customer fields.'; return false; }
            if (this.paymentMethod === 'gcash' && !this.paymentReference.trim()) {
                this.submitError = 'Enter the GCash reference number.';
                return false;
            }
            return true;
        },

        submit() {
            this.submitError = '';
            if (!this.validate()) return;

            this.submitting = true;
            const container = document.getElementById('online-fields');
            container.innerHTML = '';

            const add = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                container.appendChild(input);
            };

            this.cart.forEach((l, i) => {
                add(`items[${i}][variant_id]`, l.variantId);
                add(`items[${i}][quantity]`, l.qty);
                add(`items[${i}][price]`, l.price);
            });

            add('customer_name',    this.customerName);
            add('contact_number',   this.contactNumber);
            add('shipping_address', this.shippingAddress);
            add('landmark',         this.landmark);
            add('courier',          this.courier || 'J&T Express');
            add('shipping_fee',     this.shippingFee || 0);

            if (this.paymentMethod) {
                add('payment_method',    this.paymentMethod);
                add('amount_paid',       this.amountPaid);
                add('payment_reference', this.paymentReference);
                if (this.confirmPayment) add('confirm_payment', '1');
            }

            document.getElementById('online-form').submit();
        },
    };
}
</script>
@endpush

</x-layouts.app>
