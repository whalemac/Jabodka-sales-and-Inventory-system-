<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(protected StockService $stock) {}

    /**
     * Live stock table — all variants grouped by product.
     * Tabs: All | Low Stock.
     */
    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'all');
        $search = trim((string) $request->get('q', ''));

        $query = ProductVariant::query()
            ->with('product.supplier')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select('product_variants.*')
            ->when($filter === 'low', fn ($q) => $q->lowStock())
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

        $variants      = $query->paginate(50)->withQueryString();
        $lowStockCount = ProductVariant::query()->lowStock()->count();
        $totalVariants = ProductVariant::query()->count();

        return view('admin.stock.index', compact('variants', 'filter', 'search', 'lowStockCount', 'totalVariants'));
    }

    /**
     * Show the manual adjustment form for a specific variant.
     */
    public function adjustForm(ProductVariant $variant): View
    {
        $variant->load('product');

        return view('admin.stock.adjust', compact('variant'));
    }

    /**
     * Process a manual stock adjustment (correction / damage / consignment_return).
     */
    public function adjust(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate([
            'adjustment_type' => ['required', 'in:correction,damage,consignment_return'],
            'quantity_changed' => ['required', 'integer', 'not_in:0'],
            'reason'           => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->stock->adjust(
                variant: $variant,
                user: auth()->user(),
                quantityChanged: (int) $data['quantity_changed'],
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

    /**
     * Show the Import Products (Stock In) form.
     * Records a restock stock_adjustment linked to a supplier.
     */
    public function importForm(Request $request): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();
        $products  = Product::query()
            ->with('variants')
            ->orderBy('name')
            ->get();

        // Pre-select a variant if coming from the stock index adjust shortcut
        $preselect = $request->get('variant');

        return view('admin.stock.import', compact('suppliers', 'products', 'preselect'));
    }

    /**
     * Store one or more restock adjustments from an import delivery.
     */
    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id'         => ['nullable', 'exists:suppliers,id'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.variant_id'  => ['required', 'exists:product_variants,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'reason'              => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $data['reason'] ?? 'Supplier delivery';
        $supplierId = $data['supplier_id'] ?? null;
        $count = 0;

        foreach ($data['items'] as $line) {
            $variant = ProductVariant::query()->findOrFail($line['variant_id']);

            try {
                $this->stock->adjust(
                    variant: $variant,
                    user: auth()->user(),
                    quantityChanged: (int) $line['quantity'],
                    type: 'restock',
                    reason: $reason,
                    supplierId: $supplierId ? (int) $supplierId : null,
                );
                $count++;
            } catch (\RuntimeException $e) {
                return back()->withErrors(['items' => $e->getMessage()])->withInput();
            }
        }

        return redirect()
            ->route('admin.stock.index')
            ->with('status', $count.' variant(s) restocked successfully.');
    }
}
