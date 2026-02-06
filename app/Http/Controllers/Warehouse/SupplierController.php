<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreSupplierRequest;
use App\Http\Requests\Warehouse\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('suppliers.view');

        $suppliers = Supplier::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('contact_person', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->setPath('/warehouse/suppliers');

        return Inertia::render('warehouse/suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('suppliers.create');

        return Inertia::render('warehouse/suppliers/Create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        ActivityLogger::log('supplier.create', __('Supplier created'), $supplier, null, $supplier->toArray());

        return redirect()->route('warehouse.suppliers.index')->with('success', __('Supplier created.'));
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('suppliers.edit');

        return Inertia::render('warehouse/suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('warehouse.suppliers.index')->with('success', __('Supplier updated.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('suppliers.delete');

        $data = $supplier->toArray();
        $supplier->delete();

        ActivityLogger::log('supplier.delete', __('Supplier deleted'), null, $data, null);

        return redirect()->route('warehouse.suppliers.index')->with('success', __('Supplier deleted.'));
    }
}
