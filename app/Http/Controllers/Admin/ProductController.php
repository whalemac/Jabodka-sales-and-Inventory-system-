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
    public function index(): View
    {
        $products = Product::query()
            ->with(['supplier', 'variants'])
            ->withCount('variants')
            ->orderBy('name')
            ->paginate(30);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();

        return view('admin.products.form', ['product' => new Product, 'suppliers' => $suppliers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $variantsData = $this->validateVariants($request);

        $product = Product::query()->create($data);

        foreach ($variantsData as $v) {
            $product->variants()->create($v);
        }

        ActivityLogger::log('product.create', $product, null, $data);

        return redirect()->route('admin.products.index')->with('status', 'Product "'.$product->name.'" created.');
    }

    public function edit(Product $product): View
    {
        $suppliers = Supplier::query()->orderBy('supplier_name')->get();
        $product->load('variants');

        return view('admin.products.form', compact('product', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $variantsData = $this->validateVariants($request);

        $product->update($data);

        // Sync variants: add new ones; existing ones updated by variant_id if present
        foreach ($variantsData as $v) {
            if (! empty($v['id'])) {
                ProductVariant::query()->where('id', $v['id'])->where('product_id', $product->id)->update([
                    'size'          => $v['size'],
                    'version'       => $v['version'],
                    'reorder_level' => $v['reorder_level'],
                ]);
            } else {
                $product->variants()->create($v);
            }
        }

        ActivityLogger::log('product.update', $product, null, $data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        ActivityLogger::log('product.delete', $product, 'Deleted by admin');
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

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
            'variants'                => ['sometimes', 'array'],
            'variants.*.size'         => ['nullable', 'string', 'max:50'],
            'variants.*.version'      => ['nullable', 'string', 'max:50'],
            'variants.*.reorder_level'=> ['nullable', 'integer', 'min:0'],
        ]);

        return collect($request->input('variants', []))
            ->filter(fn ($v) => ! empty($v['size']) || ! empty($v['version']))
            ->map(fn ($v) => [
                'id'            => $v['id'] ?? null,
                'size'          => $v['size'] ?? null,
                'version'       => $v['version'] ?? null,
                'stock_count'   => $v['stock_count'] ?? 0,
                'reorder_level' => (int) ($v['reorder_level'] ?? 0),
            ])->values()->all();
    }
}
