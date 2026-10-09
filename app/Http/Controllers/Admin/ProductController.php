<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('q', ''));
        $typeFilter = $request->get('type', '');
        $statusFilter = $request->get('status', 'all');

        $products = Product::query()
            ->with(['supplier', 'variants.consignmentItems.partner'])
            ->withCount('variants')
            ->withCount(['variants as low_stock_count' => fn ($q) => $q->lowStock()])
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->when($typeFilter !== '', fn ($q) => $q->where('source_type', $typeFilter))
            ->when($statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search', 'typeFilter', 'statusFilter'));
    }

    public function create(Request $request): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();

        $data = [
            'product' => new Product,
            'suppliers' => $suppliers,
        ];

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.products._form', $data);
        }

        return view('admin.products.form', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $variantsData = $this->validateVariants($request);

        $product = Product::query()->create($data);

        foreach ($variantsData as $v) {
            $product->variants()->create($v);
        }

        // Every product must have at least one variant; create a default one if none provided
        if ($product->variants()->count() === 0) {
            $product->variants()->create([
                'size' => null,
                'version' => null,
                'stock_count' => 0,
                'reorder_level' => 0,
                'is_active' => true,
            ]);
        }

        ActivityLogger::log('product.create', $product, null, ['name' => $product->name, 'source_type' => $product->source_type]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product "'.$product->name.'" created. Review variants below.');
    }

    public function edit(Request $request, Product $product): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();
        $product->load('variants');

        // Modal popup flow: return just the form fragment for Jabodka.modalUrl().
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('admin.products._form', compact('product', 'suppliers'));
        }

        return view('admin.products.form', compact('product', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $variantsData = $this->validateVariants($request);

        $product->update($data);

        foreach ($variantsData as $v) {
            if (! empty($v['id'])) {
                ProductVariant::query()
                    ->where('id', $v['id'])
                    ->where('product_id', $product->id)
                    ->update([
                        'size' => $v['size'],
                        'version' => $v['version'],
                        'reorder_level' => $v['reorder_level'],
                        'is_active' => $v['is_active'],
                    ]);
            } else {
                $product->variants()->create([
                    'size' => $v['size'],
                    'version' => $v['version'],
                    'stock_count' => (int) ($v['stock_count'] ?? 0),
                    'reorder_level' => (int) ($v['reorder_level'] ?? 0),
                    'is_active' => $v['is_active'],
                ]);
            }
        }

        if ($product->fresh()->variants()->count() === 0) {
            $product->variants()->create(['size' => null, 'version' => null, 'stock_count' => 0, 'reorder_level' => 0, 'is_active' => true]);
        }

        ActivityLogger::log('product.update', $product, null, ['name' => $product->name]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product saved.');
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        $wasActive = $product->is_active;
        $product->update(['is_active' => ! $wasActive]);

        ActivityLogger::log(
            $product->is_active ? 'product.activate' : 'product.deactivate',
            $product,
            null,
            ['name' => $product->name, 'from' => $wasActive, 'to' => $product->is_active],
        );

        return back()->with('status', $product->is_active
            ? '"'.$product->name.'" activated.'
            : '"'.$product->name.'" deactivated. It will no longer appear in order search.');
    }

    public function toggleVariantActive(ProductVariant $variant): RedirectResponse
    {
        $wasActive = $variant->is_active;
        $variant->update(['is_active' => ! $wasActive]);

        ActivityLogger::log(
            $variant->is_active ? 'variant.activate' : 'variant.deactivate',
            $variant,
            null,
            ['from' => $wasActive, 'to' => $variant->is_active],
        );

        return back()->with('status', $variant->is_active
            ? $variant->displayName().' activated.'
            : $variant->displayName().' deactivated. It will no longer appear in order search.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:80'],
            'source_type' => ['required', 'in:handmade,sourced,consignment'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);
    }

    protected function validateVariants(Request $request): array
    {
        $request->validate([
            'variants' => ['sometimes', 'array'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.version' => ['nullable', 'string', 'max:50'],
            'variants.*.stock_count' => ['nullable', 'integer', 'min:0'],
            'variants.*.reorder_level' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ]);

        return collect($request->input('variants', []))
            ->map(fn ($v) => [
                'id' => $v['id'] ?? null,
                'size' => filled($v['size'] ?? '') ? trim($v['size']) : null,
                'version' => filled($v['version'] ?? '') ? trim($v['version']) : null,
                'stock_count' => (int) ($v['stock_count'] ?? 0),
                'reorder_level' => (int) ($v['reorder_level'] ?? 0),
                'is_active' => array_key_exists('is_active', $v)
                    ? filter_var($v['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ])
            ->values()
            ->all();
    }
}
