<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()->withCount('products')->orderBy('supplier_name')->paginate(30);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $supplier = Supplier::query()->create($data);
        ActivityLogger::log('supplier.create', $supplier);

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));
        ActivityLogger::log('supplier.update', $supplier);

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        ActivityLogger::log('supplier.delete', $supplier);
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'supplier_name'   => ['required', 'string', 'max:150'],
            'contact_details' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
