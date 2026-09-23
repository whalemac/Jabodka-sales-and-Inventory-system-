<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\SalesTransaction;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalkInOrderController extends Controller
{
    public function create(): View
    {
        return view('admin.walk-in.create');
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $variants = ProductVariant::query()
            ->with('product')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"))
                    ->orWhere('size', 'like', "%{$q}%")
                    ->orWhere('version', 'like', "%{$q}%");
            })
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->displayName(),
                'stock' => $v->stock_count,
                'price' => (float) $v->product->base_price,
                'low' => $v->isLowStock(),
            ]);

        return response()->json($variants);
    }

    public function store(Request $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'walk_in_name' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', 'in:cash,gcash'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'issue_receipt' => ['sometimes', 'boolean'],
        ]);

        try {
            $sale = $sales->checkout(auth()->user(), 'walk_in', $data['items'], [
                'walk_in_name' => $data['walk_in_name'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'payment_method' => $data['payment_method'],
                'amount_paid' => $data['amount_paid'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'confirm_payment' => true,
                'issue_receipt' => $request->boolean('issue_receipt'),
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $message = 'Sale recorded.';
        if ($sale->receipt) {
            return redirect()->route('admin.receipts.show', $sale->receipt)->with('status', $message.' Receipt issued.');
        }

        return redirect()->route('admin.walk-in.create')->with('status', $message);
    }

    public function index(): View
    {
        $sales = SalesTransaction::query()
            ->with(['items', 'customer', 'user'])
            ->where('channel', 'walk_in')
            ->latest('transaction_date')
            ->paginate(20);

        return view('admin.walk-in.index', compact('sales'));
    }
}
