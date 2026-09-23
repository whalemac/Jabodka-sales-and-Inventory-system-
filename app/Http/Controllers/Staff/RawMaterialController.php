<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Services\ProductionService;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RawMaterialController extends Controller
{
    public function __construct(protected ProductionService $production) {}

    public function index(): View
    {
        $materials = RawMaterial::query()->orderBy('material_name')->get();

        return view('staff.materials.index', compact('materials'));
    }

    public function create(): View
    {
        return view('staff.materials.form', ['material' => new RawMaterial]);
    }

    public function store(Request $request): RedirectResponse
    {
        $material = RawMaterial::query()->create($this->validated($request));
        ActivityLogger::log('material.create', $material);

        return redirect()->route('staff.materials.index')->with('status', 'Material added.');
    }

    public function edit(RawMaterial $material): View
    {
        return view('staff.materials.form', compact('material'));
    }

    public function update(Request $request, RawMaterial $material): RedirectResponse
    {
        $material->update($this->validated($request));
        ActivityLogger::log('material.update', $material);

        return redirect()->route('staff.materials.index')->with('status', 'Material updated.');
    }

    public function receive(Request $request, RawMaterial $material): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->production->receiveMaterial(auth()->user(), $material->id, (float) $data['quantity']);

        return back()->with('status', number_format($data['quantity'], 2).' '.$material->unit.' of "'.$material->material_name.'" received.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'material_name'  => ['required', 'string', 'max:150'],
            'unit'           => ['required', 'string', 'max:30'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
