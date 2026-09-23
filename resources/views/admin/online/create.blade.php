<x-layouts.app title="New Online Order">

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('admin.online.index') }}" class="text-gray-500 hover:text-navy">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-ink">New Online Order</h1>
</div>

<div x-data="onlineOrder()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- LEFT: Product search + cart --}}
    <div class="lg:col-span-3 space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <label class="block text-sm font-semibold text-ink mb-2">Search Products</label>
            <div class="relative">
                <input type="text" x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []"
                       placeholder="Type product name, size, or version…"
                       class="w-full h-12 pl-10 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy"
                       autocomplete="off">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div x-show="results.length > 0" x-cloak class="mt-2 border border-gray-100 rounded-xl overflow-hidden shadow-lg">
                <template x-for="item in results" :key="item.id">
                    <button type="button" @click="addToCart(item)" :disabled="item.stock === 0"
                            class="w-full flex items-center justify-between px-4 py-3 hover:bg-sand text-left border-b border-gray-50 last:border-0 transition"
                            :class="item.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-400" x-text="item.stock + ' in stock'"></p>
                        </div>
                        <p class="text-sm font-semibold text-navy ml-4 shrink-0" x-text="'₱' + item.price.toFixed(2)"></p>
                    </button>
                </template>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-ink text-sm">Order Items</h2>
                <span class="text-xs text-gray-400" x-text="cart.length + ' item(s)'"></span>
            </div>
            <div x-show="cart.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">Search above to add items.</div>
            <div x-show="cart.length > 0" class="divide-y divide-gray-50">
                <template x-for="(line, idx) in cart" :key="line.variantId">
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink truncate" x-text="line.name"></p>
                            <p class="text-xs text-gray-400" x-text="'₱' + line.price.toFixed(2) + ' each'"></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="if(line.qty>1) line.qty--; else cart.splice(idx,1)"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold text-gray-600">−</button>
                            <span class="w-8 text-center text-sm font-semibold" x-text="line.qty"></span>
                            <button type="button" @click="if(line.qty < line.maxStock) line.qty++"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center font-bold text-gray-600">+</button>
                        </div>
                        <p class="w-20 text-right text-sm font-semibold text-ink shrink-0" x-text="'₱' + (line.price * line.qty).toFixed(2)"></p>
                        <button type="button" @click="cart.splice(idx,1)" class="w-7 h-7 rounded-lg hover:bg-red-50 flex items-center justify-center text-gray-400 hover:text-red-500">×</button>
                    </div>
                </template>
            </div>
            <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 bg-sand/50 space-y-1">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal</span><span x-text="'₱' + cartTotal().toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Shipping</span><span x-text="'₱' + parseFloat(shippingFee || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between font-bold text-base text-ink pt-1 border-t border-gray-200">
                    <span>Grand Total</span><span x-text="'₱' + grandTotal().toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Customer + Shipping + Payment --}}
    <div class="lg:col-span-2 space-y-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-ink text-sm mb-1">Customer & Shipping</h2>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="customerName" required placeholder="Facebook name or full name"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Contact Number <span class="text-red-500">*</span></label>
                <input type="text" x-model="contactNumber" required placeholder="09xx-xxx-xxxx"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Shipping Address <span class="text-red-500">*</span></label>
                <textarea x-model="shippingAddress" required rows="2" placeholder="Full address"
                          class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-sm resize-none focus:outline-none focus:border-navy"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Landmark</label>
                <input type="text" x-model="landmark" placeholder="Nearby landmark"
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
                    <input type="number" step="0.01" min="0" x-model.number="shippingFee" placeholder="0.00"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-ink text-sm mb-1">Payment (optional)</h2>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="paymentMethod = 'cash'"
                        :class="paymentMethod === 'cash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200'"
                        class="h-10 rounded-xl border-2 text-sm font-semibold transition">Cash</button>
                <button type="button" @click="paymentMethod = 'gcash'"
                        :class="paymentMethod === 'gcash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200'"
                        class="h-10 rounded-xl border-2 text-sm font-semibold transition">GCash</button>
            </div>
            <div x-show="paymentMethod">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Amount Paid (₱)</label>
                <input type="number" step="0.01" min="0" x-model.number="amountPaid" placeholder="0.00"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
            </div>
            <div x-show="paymentMethod === 'gcash'" x-cloak>
                <label class="block text-xs font-semibold text-gray-600 mb-1">GCash Reference #</label>
                <input type="text" x-model="paymentReference" placeholder="Reference number"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
            </div>
            <label class="flex items-center gap-2 cursor-pointer" x-show="amountPaid > 0">
                <input type="checkbox" x-model="confirmPayment" class="w-4 h-4 rounded border-gray-300 text-navy">
                <span class="text-sm text-gray-600">Mark payment as confirmed</span>
            </label>
        </div>

        <div>
            <p x-show="submitError" x-cloak x-text="submitError"
               class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-3"></p>

            <form id="online-form" method="POST" action="{{ route('admin.online.store') }}">
                @csrf
                <div id="online-fields"></div>
            </form>

            <button type="button" @click="submit()" :disabled="cart.length === 0 || submitting"
                    class="w-full h-14 bg-navy hover:bg-navy-dark text-white font-bold text-base rounded-2xl
                           transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-md">
                <span x-text="submitting ? 'Saving…' : 'Save Online Order'"></span>
            </button>
            <p class="text-center text-xs text-gray-400 mt-2">Order starts as "Awaiting Payment" unless payment is confirmed.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function onlineOrder() {
    return {
        query: '', results: [], cart: [],
        customerName: '', contactNumber: '', shippingAddress: '', landmark: '',
        courier: 'J&T Express', shippingFee: 0,
        paymentMethod: '', amountPaid: 0, paymentReference: '', confirmPayment: false,
        submitting: false, submitError: '',

        init() {},

        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch(`{{ route('admin.walk-in.search') }}?q=` + encodeURIComponent(this.query));
            this.results = await res.json();
        },

        addToCart(item) {
            if (item.stock === 0) return;
            const ex = this.cart.find(l => l.variantId === item.id);
            if (ex) { if (ex.qty < ex.maxStock) ex.qty++; }
            else this.cart.push({ variantId: item.id, name: item.name, price: item.price, qty: 1, maxStock: item.stock });
            this.query = ''; this.results = [];
        },

        cartTotal() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
        grandTotal() { return this.cartTotal() + parseFloat(this.shippingFee || 0); },

        submit() {
            this.submitError = '';
            if (!this.cart.length) { this.submitError = 'Add at least one item.'; return; }
            if (!this.customerName.trim()) { this.submitError = 'Customer name is required.'; return; }
            if (!this.contactNumber.trim()) { this.submitError = 'Contact number is required.'; return; }
            if (!this.shippingAddress.trim()) { this.submitError = 'Shipping address is required.'; return; }
            this.submitting = true;

            const form = document.getElementById('online-form');
            const c = document.getElementById('online-fields');
            c.innerHTML = '';
            const add = (n, v) => { const i = document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; c.appendChild(i); };

            this.cart.forEach((l, i) => {
                add(`items[${i}][variant_id]`, l.variantId);
                add(`items[${i}][quantity]`, l.qty);
                add(`items[${i}][price]`, l.price);
            });
            add('customer_name', this.customerName);
            add('contact_number', this.contactNumber);
            add('shipping_address', this.shippingAddress);
            add('landmark', this.landmark);
            add('courier', this.courier || 'J&T Express');
            add('shipping_fee', this.shippingFee || 0);
            if (this.paymentMethod) {
                add('payment_method', this.paymentMethod);
                add('amount_paid', this.amountPaid);
                add('payment_reference', this.paymentReference);
                if (this.confirmPayment) add('confirm_payment', '1');
            }
            form.submit();
        },
    };
}
</script>
@endpush

</x-layouts.app>
