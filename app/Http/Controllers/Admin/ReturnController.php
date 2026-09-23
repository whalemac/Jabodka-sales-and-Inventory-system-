<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Services\StockService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function __construct(protected StockService $stock) {}

    // ─── Index: list all returns with filters ─────────────────────────────────

    public function index(Request $request): View
    {
        $typeFilter = $request->get('type', '');

        $returns = ProductReturn::query()
            ->with([
                'salesItem.variant.product',
                'salesItem.transaction.customer',
                'user',
            ])
            ->when($typeFilter !== '', fn ($q) => $q->where('return_type', $typeFilter))
            ->latest('return_date')
            ->paginate(20)
            ->withQueryString();

        $totalRefunds      = ProductReturn::where('return_type', 'refund')->count();
        $totalReplacements = ProductReturn::where('return_type', 'replacement')->count();

        return view('admin.returns.index', compact('returns', 'typeFilter', 'totalRefunds', 'totalReplacements'));
    }

    // ─── Search transactions to find the item to return ───────────────────────

    public function search(Request $request): View|JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        // JSON response for Alpine search
        if ($request->wantsJson() || $request->has('json')) {
            $transactions = SalesTransaction::query()
                ->with(['items.variant.product', 'items.returns', 'customer'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                              ->orWhereHas('items.variant.product', fn ($p) => $p->where('name', 'like', "%{$q}%"))
                              ->orWhere('id', is_numeric($q) ? (int) $q : 0);
                    });
                })
                ->latest('transaction_date')
                ->limit(10)
                ->get()
                ->map(fn (SalesTransaction $tx) => [
                    'id'       => $tx->id,
                    'label'    => '#'.$tx->id.' · '.($tx->customer?->name ?? 'Anonymous').' · '.$tx->transaction_date->format('M j, Y'),
                    'channel'  => $tx->channel,
                    'date'     => $tx->transaction_date->format('M j, Y g:i A'),
                    'customer' => $tx->customer?->name ?? 'Anonymous',
                    'total'    => $tx->grandTotal(),
                    'items'    => $tx->items->map(fn (SalesItem $item) => [
                        'id'          => $item->id,
                        'name'        => $item->variant->displayName(),
                        'qty'         => $item->quantity,
                        'price'       => (float) $item->price_at_sale,
                        'line_total'  => $item->lineTotal(),
                        'has_return'  => $item->returns->isNotEmpty(),
                        'return_url'  => route('admin.returns.create', $item),
                    ])->values()->all(),
                ]);

            return response()->json($transactions);
        }

        // Full-page view
        $transactions = SalesTransaction::query()
            ->with(['items.variant.product', 'items.returns', 'customer'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                          ->orWhereHas('items.variant.product', fn ($p) => $p->where('name', 'like', "%{$q}%"))
                          ->orWhere('id', is_numeric($q) ? (int) $q : 0);
                });
            })
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.returns.search', compact('transactions', 'q'));
    }

    // ─── Create return form for a specific sales item ─────────────────────────

    public function create(SalesItem $salesItem): View|RedirectResponse
    {
        $salesItem->load(['variant.product', 'transaction.customer', 'returns']);

        // Block if this item already has an open return
        if ($salesItem->returns->isNotEmpty()) {
            return redirect()
                ->route('admin.returns.index')
                ->withErrors(['duplicate' => 'A return has already been recorded for this item.']);
        }

        return view('admin.returns.create', compact('salesItem'));
    }

    // ─── Store a new return ───────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_item_id' => ['required', 'exists:sales_items,id'],
            'return_type'   => ['required', 'in:refund,replacement'],
            'reason'        => ['required', 'string', 'max:500'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'restock'       => ['sometimes', 'boolean'],
        ]);

        // Guard: prevent duplicate returns on the same item
        $alreadyReturned = ProductReturn::where('sales_item_id', $data['sales_item_id'])->exists();
        if ($alreadyReturned) {
            return back()->withErrors(['sales_item_id' => 'A return has already been recorded for this item.']);
        }

        $return = DB::transaction(function () use ($data) {
            $salesItem = SalesItem::query()->with('variant')->findOrFail($data['sales_item_id']);

            $return = ProductReturn::query()->create([
                'sales_item_id' => $salesItem->id,
                'user_id'       => auth()->id(),
                'return_type'   => $data['return_type'],
                'reason'        => $data['reason'],
                'refund_amount' => $data['return_type'] === 'refund' ? ($data['refund_amount'] ?? null) : null,
                'return_date'   => now(),
                'restocked'     => false,
            ]);

            // Restock if replacement and item is physically returned to shelf
            if ($data['return_type'] === 'replacement' && ! empty($data['restock'])) {
                $this->stock->increment($salesItem->variant, $salesItem->quantity);
                $return->update(['restocked' => true]);
            }

            ActivityLogger::log('return.create', $return, $data['reason'], [
                'return_type'   => $data['return_type'],
                'sales_item_id' => $salesItem->id,
                'restocked'     => $return->restocked,
            ]);

            return $return;
        });

        return redirect()
            ->route('admin.returns.show', $return)
            ->with('status', ucfirst($data['return_type']).' recorded successfully.');
    }

    // ─── Show a single return ─────────────────────────────────────────────────

    public function show(ProductReturn $return): View
    {
        $return->load([
            'salesItem.variant.product',
            'salesItem.transaction.customer',
            'salesItem.transaction.payments',
            'user',
        ]);

        return view('admin.returns.show', compact('return'));
    }
}
