<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ProductionLog;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(protected ProductionService $production) {}

    public function index(): View
    {
        $logs = ProductionLog::query()
            ->with(['variant.product', 'user', 'materialTransactions.material'])
            ->where('user_id', auth()->id())
            ->latest('production_date')
            ->paginate(20);

        return view('staff.production.index', compact('logs'));
    }

    public function create(): View
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('source_type', 'handmade'))
            ->orderBy('product_id')
            ->get();

        $materials = RawMaterial::query()->orderBy('material_name')->get();

        return view('staff.production.create', compact('variants', 'materials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id'        => ['required', 'exists:product_variants,id'],
            'quantity_produced' => ['required', 'integer', 'min:1'],
            'production_date'   => ['required', 'date', 'before_or_equal:today'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'materials'                 => ['sometimes', 'array'],
            'materials.*.material_id'   => ['required', 'exists:raw_materials,id'],
            'materials.*.quantity'      => ['required', 'numeric', 'min:0.01'],
        ]);

        $materialsUsed = collect($request->input('materials', []))
            ->filter(fn ($m) => ! empty($m['material_id']) && ($m['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        try {
            $this->production->logProduction(auth()->user(), $data, $materialsUsed);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['materials' => $e->getMessage()])->withInput();
        }

        return redirect()->route('staff.production.index')->with('status', 'Production logged successfully.');
    }

    public function show(ProductionLog $log): View
    {
        // Staff can only view their own logs
        abort_unless($log->user_id === auth()->id(), 403);

        $log->load(['variant.product', 'materialTransactions.material', 'user']);

        return view('staff.production.show', compact('log'));
    }
}
