<x-layouts.app title="Record Consignment Drop-off">

    <div class="mb-6">
        <a href="{{ route('admin.consignment.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-navy mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Consignment
        </a>
        <h1 class="text-2xl font-bold text-ink">Record Consignment Drop-off</h1>
        <p class="text-sm text-gray-500 mt-0.5">One visit from a partner, many items. Search, add lines, then save the batch.</p>
    </div>

    @include('admin.consignment._receipt-form')

    @push('scripts')
    <script>
    // consignmentCart is also defined globally in jabodka.js so drop-off works inside modals.
    if (typeof consignmentCart !== 'function') {
        function consignmentCart(catalog) {
            return {
                catalog, query: '', matches: [], lines: [],
                draft: { variant_id: '', label: '', qty: 1, base: '', markup: '' },
                filter() {
                    const q = this.query.toLowerCase().trim();
                    this.matches = q.length < 1 ? [] : this.catalog.filter(v => v.label.toLowerCase().includes(q)).slice(0, 8);
                },
                pick(v) {
                    this.draft.variant_id = v.id;
                    this.draft.label = v.label;
                    this.query = v.label;
                    this.matches = [];
                },
                addLine() {
                    if (!this.draft.variant_id) return;
                    this.lines.push({ ...this.draft, qty: this.draft.qty || 1, base: this.draft.base || 0, markup: this.draft.markup || 0 });
                    this.draft = { variant_id: '', label: '', qty: 1, base: '', markup: '' };
                    this.query = '';
                },
            };
        }
        window.consignmentCart = consignmentCart;
    }
    </script>
    @endpush
</x-layouts.app>
