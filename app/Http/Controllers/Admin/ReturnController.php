<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\SalesItem;
use App\Services\StockService;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function __construct(protected StockService $stock) {}

    public function index(): View
    {
        $returns = ProductReturn::query()
            ->with(['salesItem.variant.product', 'salesItem.transaction', 'user'])
            ->latest('return_date')
            ->paginate(20);

        return view('admin.returns.index', compact('returns'));
    }

    public function create(SalesItem $salesItem): View
    {
        $salesItem->load(['variant.product', 'transaction.customer']);

        return view('admin.returns.create', compact('salesItem'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sales_item_id' => ['required', 'exists:sales_items,id'],
            'return_type'   => ['required', 'in:refund,replacement'],
            'reason'        => ['required', 'string', 'max:500'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'restock'       => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $salesItem = SalesItem::query()->with('variant')->findOrFail($data['sales_item_id']);

            $return = ProductReturn::query()->create([
                'sales_item_id' => $salesItem->id,
                'user_id'       => auth()->id(),
                'return_type'   => $data['return_type'],
                'reason'        => $data['reason'],
                'refund_amount' => $data['refund_amount'] ?? null,
                'return_date'   => now(),
                'restocked'     => false,
            ]);

            // Restock if replacement and item is physically returned
            if ($data['return_type'] === 'replacement' && ! empty($data['restock'])) {
                $this->stock->increment($salesItem->variant, $salesItem->quantity);
                $return->update(['restocked' => true]);
            }

            ActivityLogger::log('return.create', $return, $data['reason'], $data);
        });

        return redirect()->route('admin.returns.index')->with('status', 'Return recorded.');
    }
}
