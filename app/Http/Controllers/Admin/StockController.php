<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReceipt;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(protected StockService $stock) {}

    /**
     * Live stock table plus supplier invoices.
     */
    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'all');
        $type = $request->get('type', '');
        $search = trim((string) $request->get('q', ''));

        $query = ProductVariant::query()
            ->with(['product.supplier', 'consignmentItems.partner'])
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('product_variants.*')
            ->when($filter === 'low', fn ($q) => $q->lowStock())
            ->when(in_array($type, ['handmade', 'sourced', 'consignment'], true), fn ($q) => $q->where('products.source_type', $type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('products.name', 'like', "%{$search}%")
                        ->orWhere('product_variants.size', 'like', "%{$search}%")
                        ->orWhere('product_variants.version', 'like', "%{$search}%")
                        ->orWhere('products.category', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('CASE WHEN product_variants.stock_count = 0 THEN 0 WHEN product_variants.stock_count <= product_variants.reorder_level THEN 1 ELSE 2 END')
            ->orderBy('products.name')
            ->orderBy('product_variants.size')
            ->orderBy('product_variants.version');

        $variants = $query->paginate(25)->withQueryString();

        $lowStockQuery = ProductVariant::query()->lowStock();
        $totalQuery = ProductVariant::query();
        if (in_array($type, ['handmade', 'sourced', 'consignment'], true)) {
            $lowStockQuery->whereHas('product', fn ($q) => $q->where('source_type', $type));
            $totalQuery->whereHas('product', fn ($q) => $q->where('source_type', $type));
        }
        $lowStockCount = $lowStockQuery->count();
        $totalVariants = $totalQuery->count();

        $receipts = StockReceipt::query()
            ->with(['supplier', 'user', 'items.variant.product'])
            ->latest('received_date')
            ->latest('id')
            ->paginate(10, ['*'], 'ipage');

        return view('admin.stock.index', compact(
            'variants', 'filter', 'type', 'search', 'lowStockCount', 'totalVariants', 'receipts'
        ));
    }

    public function adjustForm(Request $request, ProductVariant $variant): View
    {
        $variant->load('product');

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.stock._adjust', compact('variant'));
        }

        return view('admin.stock.adjust', compact('variant'));
    }

    public function adjust(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate([
            'adjustment_type' => ['required', 'in:correction,damage,consignment_return'],
            'quantity_changed' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $qty = (int) $data['quantity_changed'];
        // For damage/consignment_return, coerce to negative value (deduction)
        if (in_array($data['adjustment_type'], ['damage', 'consignment_return'])) {
            $qty = -abs($qty);
        }

        try {
            $this->stock->adjust(
                variant: $variant,
                user: auth()->user(),
                quantityChanged: $qty,
                type: $data['adjustment_type'],
                reason: $data['reason'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity_changed' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.stock.index')
            ->with('status', 'Stock adjusted for '.$variant->displayName().'.');
    }

    public function importForm(Request $request): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();
        $products = Product::query()
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->where('source_type', '!=', 'consignment')
            ->orderBy('name')
            ->get();

        $preselect = $request->get('variant');
        $nextInvoiceNumber = $this->nextInvoiceNumber();

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.stock._import', compact('suppliers', 'products', 'preselect', 'nextInvoiceNumber'));
        }

        return view('admin.stock.import', compact('suppliers', 'products', 'preselect', 'nextInvoiceNumber'));
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Supplier invoices must never carry consignment stock — those units are
        // tracked through the consignment receipt flow so they stay attributable.
        $consignmentVariantIds = ProductVariant::query()
            ->whereIn('id', array_column($data['items'], 'variant_id'))
            ->whereHas('product', fn ($q) => $q->where('source_type', 'consignment'))
            ->pluck('id');

        if ($consignmentVariantIds->isNotEmpty()) {
            return back()
                ->withErrors(['items' => 'Consignment products cannot be received on a supplier invoice. Record them under Consignment → Record drop-off instead.'])
                ->withInput();
        }

        $invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));
        if ($invoiceNumber === '' || StockReceipt::query()->where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = $this->nextInvoiceNumber();
        }

        try {
            $receipt = $this->stock->receiveInvoice(
                user: auth()->user(),
                invoiceNumber: $invoiceNumber,
                receivedDate: $data['received_date'],
                lines: $data['items'],
                supplierId: (int) $data['supplier_id'],
                notes: $data['notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.stock.index')
            ->with('status', 'Invoice '.$receipt->invoice_number.' recorded with '.$receipt->items->count().' line(s).');
    }

    /**
     * Auto invoice number: INV-YYYYMMDD-0001, sequential per day.
     */
    protected function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.today()->format('Ymd').'-';

        $last = StockReceipt::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;
        if (is_string($last) && str_starts_with($last, $prefix)) {
            $sequence = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
