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
        $search     = trim((string) $request->get('q', ''));
        $typeFilter = $request->get('type', '');

        $products = Product::query()
            ->with(['supplier', 'variants'])
            ->withCount('variants')
            ->withCount(['variants as low_stock_count' => fn ($q) => $q->lowStock()])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%"))
            ->when($typeFilter !== '', fn ($q) => $q->where('source_type', $typeFilter))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search', 'typeFilter'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();

        return view('admin.products.form', [
            'product'   => new Product,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data         = $this->validated($request);
        $variantsData = $this->validateVariants($request);

        $product = Product::query()->create($data);

        foreach ($variantsData as $v) {
            $product->variants()->create($v);
        }

        // Every product must have at least one variant; create a default one if none provided
        if ($product->variants()->count() === 0) {
            $product->variants()->create([
                'size'          => null,
                'version'       => null,
                'stock_count'   => 0,
                'reorder_level' => 0,
            ]);
        }

        ActivityLogger::log('product.create', $product, null, ['name' => $product->name, 'source_type' => $product->source_type]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product "'.$product->name.'" created. Review variants below.');
    }

    public function edit(Product $product): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();
        $product->load('variants');

        return view('admin.products.form', compact('product', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data         = $this->validated($request, $product);
        $variantsData = $this->validateVariants($request);
        $deleteIds    = array_filter(explode(',', $request->input('delete_variant_ids', '')));

        $product->update($data);

        // Delete removed variants (only those with no sales history)
        if ($deleteIds) {
            $safeToDelete = ProductVariant::query()
                ->whereIn('id', $deleteIds)
                ->where('product_id', $product->id)
                ->whereDoesntHave('salesItems')
                ->pluck('id');

            ProductVariant::query()->whereIn('id', $safeToDelete)->delete();
        }

        // Upsert variants from form
        $submittedIds = [];
        foreach ($variantsData as $v) {
            if (! empty($v['id'])) {
                // Update existing variant — never touch stock_count here (that's StockService's job)
                ProductVariant::query()
                    ->where('id', $v['id'])
                    ->where('product_id', $product->id)
                    ->update([
                        'size'          => $v['size'],
                        'version'       => $v['version'],
                        'reorder_level' => $v['reorder_level'],
                    ]);
                $submittedIds[] = $v['id'];
            } else {
                // New variant — allow setting opening stock on create
                $newVariant = $product->variants()->create([
                    'size'          => $v['size'],
                    'version'       => $v['version'],
                    'stock_count'   => (int) ($v['stock_count'] ?? 0),
                    'reorder_level' => (int) ($v['reorder_level'] ?? 0),
                ]);
                $submittedIds[] = $newVariant->id;
            }
        }

        // Guard: if all variants were removed, keep at least the existing ones
        if ($product->fresh()->variants()->count() === 0) {
            $product->variants()->create(['size' => null, 'version' => null, 'stock_count' => 0, 'reorder_level' => 0]);
        }

        ActivityLogger::log('product.update', $product, null, ['name' => $product->name]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Prevent deleting a product that has been sold
        $hasSales = $product->variants()->whereHas('salesItems')->exists();
        if ($hasSales) {
            return back()->withErrors([
                'delete' => '"'.$product->name.'" has sales history and cannot be deleted. Archive it instead.',
            ]);
        }

        ActivityLogger::log('product.delete', $product, 'Deleted by admin', ['name' => $product->name]);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'category'    => ['nullable', 'string', 'max:80'],
            'source_type' => ['required', 'in:handmade,sourced,consignment'],
            'base_price'  => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);
    }

    protected function validateVariants(Request $request): array
    {
        $request->validate([
            'variants'                 => ['sometimes', 'array'],
            'variants.*.size'          => ['nullable', 'string', 'max:50'],
            'variants.*.version'       => ['nullable', 'string', 'max:50'],
            'variants.*.stock_count'   => ['nullable', 'integer', 'min:0'],
            'variants.*.reorder_level' => ['nullable', 'integer', 'min:0'],
        ]);

        return collect($request->input('variants', []))
            ->map(fn ($v) => [
                'id'            => $v['id'] ?? null,
                'size'          => filled($v['size'] ?? '')  ? trim($v['size'])    : null,
                'version'       => filled($v['version'] ?? '') ? trim($v['version']) : null,
                'stock_count'   => (int) ($v['stock_count']   ?? 0),
                'reorder_level' => (int) ($v['reorder_level'] ?? 0),
            ])
            ->values()
            ->all();
    }
}
