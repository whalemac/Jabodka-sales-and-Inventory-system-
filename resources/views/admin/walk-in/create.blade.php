<x-layouts.app title="New Walk-In Sale">

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('admin.walk-in.index') }}" class="text-gray-500 hover:text-navy">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-ink">New Walk-In Sale</h1>
</div>

<div
    x-data="posCart()"
    x-init="init()"
    class="grid grid-cols-1 lg:grid-cols-5 gap-5"
>
    {{-- ===== LEFT: Product Search + Cart ===== --}}
    <div class="lg:col-span-3 space-y-4">

        {{-- Search bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <label class="block text-sm font-semibold text-ink mb-2">Search Products</label>
            <div class="relative">
                <input
                    type="text"
                    x-model="query"
                    @input.debounce.300ms="search()"
                    @keydown.escape="results = []"
                    placeholder="Type product name, size, or version…"
                    class="w-full h-12 pl-10 pr-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy"
                    autocomplete="off"
                >
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Search results dropdown --}}
            <div x-show="results.length > 0" x-cloak class="mt-2 border border-gray-100 rounded-xl overflow-hidden shadow-lg">
                <template x-for="item in results" :key="item.id">
                    <button
                        type="button"
                        @click="addToCart(item)"
                        class="w-full flex items-center justify-between px-4 py-3 hover:bg-sand text-left border-b border-gray-50 last:border-0 transition"
                        :class="item.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                        :disabled="item.stock === 0"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink truncate" x-text="item.name"></p>
                            <p class="text-xs mt-0.5" :class="item.low ? 'text-red-500 font-semibold' : 'text-gray-400'">
                                <span x-text="item.low ? '⚠ Low stock — ' : ''"></span>
                                <span x-text="item.stock + ' in stock'"></span>
                            </p>
                        </div>
                        <div class="ml-4 shrink-0 text-right">
                            <p class="text-sm font-semibold text-navy" x-text="'₱' + item.price.toFixed(2)"></p>
                            <p x-show="item.stock === 0" class="text-xs text-red-500">Out of stock</p>
                        </div>
                    </button>
                </template>
            </div>
            <p x-show="query.length >= 2 && results.length === 0 && !searching" x-cloak class="mt-2 text-sm text-gray-400 px-1">No products found.</p>
        </div>

        {{-- Cart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-ink text-sm">Cart</h2>
                <span class="text-xs text-gray-400" x-text="cart.length + ' item(s)'"></span>
            </div>

            {{-- Empty cart --}}
            <div x-show="cart.length === 0" class="px-5 py-10 text-center">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18l-2 13H5L3 3zm0 0l-.5-2H1M9 21a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
                <p class="text-sm text-gray-400">Search above and tap a product to add it.</p>
            </div>

            {{-- Cart items --}}
            <div x-show="cart.length > 0" class="divide-y divide-gray-50">
                <template x-for="(line, idx) in cart" :key="line.variantId">
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-ink truncate" x-text="line.name"></p>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="'₱' + line.price.toFixed(2) + ' each'"></p>
                        </div>
                        {{-- Qty controls --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="decrement(idx)"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition font-bold text-gray-600">
                                −
                            </button>
                            <input type="number" x-model.number="line.qty" @change="clampQty(idx)"
                                   min="1" :max="line.maxStock"
                                   class="w-14 h-8 text-center rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-navy">
                            <button type="button" @click="increment(idx)"
                                    class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition font-bold text-gray-600"
                                    :disabled="line.qty >= line.maxStock">
                                +
                            </button>
                        </div>
                        {{-- Line total --}}
                        <p class="w-20 text-right text-sm font-semibold text-ink shrink-0"
                           x-text="'₱' + (line.price * line.qty).toFixed(2)"></p>
                        {{-- Remove --}}
                        <button type="button" @click="remove(idx)"
                                class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center text-gray-400 hover:text-red-500 transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Cart totals --}}
            <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 bg-sand/50">
                <div class="flex justify-between text-base font-bold text-ink">
                    <span>Total</span>
                    <span x-text="'₱' + cartTotal().toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: Customer + Payment + Submit ===== --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Customer info (optional) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-ink text-sm mb-4">Customer (optional)</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Name</label>
                    <input type="text" x-model="customerName" placeholder="Walk-in customer"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Contact Number</label>
                    <input type="text" x-model="contactNumber" placeholder="09xx-xxx-xxxx"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-semibold text-ink text-sm mb-4">Payment</h2>
            <div class="space-y-4">
                {{-- Method toggle --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Method</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="paymentMethod = 'cash'"
                                :class="paymentMethod === 'cash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy'"
                                class="h-11 rounded-xl border-2 text-sm font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Cash
                        </button>
                        <button type="button" @click="paymentMethod = 'gcash'"
                                :class="paymentMethod === 'gcash' ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200 hover:border-navy'"
                                class="h-11 rounded-xl border-2 text-sm font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            GCash
                        </button>
                    </div>
                </div>

                {{-- Amount paid --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Amount Tendered (₱)</label>
                    <input type="number" step="0.01" min="0" x-model.number="amountPaid"
                           :placeholder="cartTotal().toFixed(2)"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>

                {{-- GCash reference --}}
                <div x-show="paymentMethod === 'gcash'" x-cloak>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">GCash Reference #</label>
                    <input type="text" x-model="paymentReference" placeholder="e.g. 1234567890"
                           class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
                </div>

                {{-- Change --}}
                <div x-show="amountPaid > 0" x-cloak
                     class="flex justify-between items-center bg-sand rounded-xl px-4 py-3">
                    <span class="text-sm text-gray-600">Change</span>
                    <span class="font-bold text-lg"
                          :class="change() < 0 ? 'text-red-500' : 'text-ink'"
                          x-text="'₱' + Math.max(0, change()).toFixed(2)">
                    </span>
                </div>

                {{-- Issue receipt --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" x-model="issueReceipt"
                           class="w-5 h-5 rounded border-gray-300 text-navy focus:ring-navy">
                    <span class="text-sm text-gray-700">Print receipt for this sale</span>
                </label>
            </div>
        </div>

        {{-- Submit button + validation --}}
        <div>
            <p x-show="submitError" x-cloak x-text="submitError"
               class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-3"></p>

            {{-- Hidden form submitted via JS --}}
            <form id="pos-form" method="POST" action="{{ route('admin.walk-in.store') }}">
                @csrf
                <div id="form-fields"></div>
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
                <svg x-show="!submitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="submitting ? 'Processing…' : 'Complete Sale'"></span>
            </button>

            <p class="text-center text-xs text-gray-400 mt-2">
                Stock will be deducted immediately on completion.
            </p>
        </div>
    </div>
</div>

{{-- Server-side validation errors (from redirect back) --}}
@if($errors->any())
<div class="fixed bottom-4 right-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 shadow-lg max-w-sm" x-data="{ show: true }" x-show="show" x-cloak>
    <p class="text-sm font-semibold text-red-700">{{ $errors->first() }}</p>
    <button @click="show = false" class="absolute top-2 right-3 text-red-400 hover:text-red-600">×</button>
</div>
@endif

@push('scripts')
<script>
function posCart() {
    return {
        query: '',
        results: [],
        searching: false,
        cart: [],
        customerName: '',
        contactNumber: '',
        paymentMethod: 'cash',
        amountPaid: 0,
        paymentReference: '',
        issueReceipt: false,
        submitting: false,
        submitError: '',

        init() {
            // Focus search on load
            this.$nextTick(() => {
                document.querySelector('input[placeholder*="Type product"]')?.focus();
            });
        },

        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            this.searching = true;
            try {
                const res = await fetch(`{{ route('admin.walk-in.search') }}?q=` + encodeURIComponent(this.query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
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
                    variantId: item.id,
                    name: item.name,
                    price: item.price,
                    qty: 1,
                    maxStock: item.stock,
                });
            }
            this.query = '';
            this.results = [];
        },

        increment(idx) {
            const line = this.cart[idx];
            if (line.qty < line.maxStock) line.qty++;
        },

        decrement(idx) {
            if (this.cart[idx].qty > 1) {
                this.cart[idx].qty--;
            } else {
                this.remove(idx);
            }
        },

        clampQty(idx) {
            const line = this.cart[idx];
            line.qty = Math.max(1, Math.min(line.maxStock, parseInt(line.qty) || 1));
        },

        remove(idx) {
            this.cart.splice(idx, 1);
        },

        cartTotal() {
            return this.cart.reduce((sum, l) => sum + l.price * l.qty, 0);
        },

        change() {
            return this.amountPaid - this.cartTotal();
        },

        submit() {
            this.submitError = '';
            if (this.cart.length === 0) { this.submitError = 'Add at least one item.'; return; }
            if (!this.paymentMethod) { this.submitError = 'Select a payment method.'; return; }
            if (this.amountPaid <= 0) { this.submitError = 'Enter the amount paid.'; return; }
            if (this.amountPaid < this.cartTotal()) {
                this.submitError = 'Amount paid is less than the total (₱' + this.cartTotal().toFixed(2) + ').';
                return;
            }
            if (this.paymentMethod === 'gcash' && !this.paymentReference.trim()) {
                this.submitError = 'Enter the GCash reference number.';
                return;
            }

            this.submitting = true;
            const form = document.getElementById('pos-form');
            const container = document.getElementById('form-fields');
            container.innerHTML = '';

            const add = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                container.appendChild(input);
            };

            this.cart.forEach((line, i) => {
                add(`items[${i}][variant_id]`, line.variantId);
                add(`items[${i}][quantity]`, line.qty);
                add(`items[${i}][price]`, line.price);
            });

            add('walk_in_name', this.customerName);
            add('contact_number', this.contactNumber);
            add('payment_method', this.paymentMethod);
            add('amount_paid', this.amountPaid);
            add('payment_reference', this.paymentReference);
            if (this.issueReceipt) add('issue_receipt', '1');

            form.submit();
        },
    };
}
</script>
@endpush

</x-layouts.app>
