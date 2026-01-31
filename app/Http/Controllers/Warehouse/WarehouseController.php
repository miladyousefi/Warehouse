<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse as WarehouseModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('warehouses.view');

        $warehouses = WarehouseModel::query()
            ->when($request->search, fn ($q) => $q->where('name_tr', 'like', "%{$request->search}%")
                ->orWhere('name_en', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('warehouse/warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('warehouses.create');

        return Inertia::render('warehouse/warehouses/Create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        WarehouseModel::create($request->validated());

        return redirect()->route('warehouse.warehouses.index')->with('success', __('Warehouse created.'));
    }

    public function edit(WarehouseModel $warehouse): Response
    {
        $this->authorize('warehouses.edit');

        return Inertia::render('warehouse/warehouses/Edit', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(UpdateWarehouseRequest $request, WarehouseModel $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()->route('warehouse.warehouses.index')->with('success', __('Warehouse updated.'));
    }

    public function destroy(WarehouseModel $warehouse): RedirectResponse
    {
        $this->authorize('warehouses.delete');

        if ($warehouse->stockBalances()->exists() || $warehouse->stockMovements()->exists()) {
            return back()->with('error', __('Warehouse has stock data and cannot be deleted.'));
        }

        $warehouse->delete();

        return redirect()->route('warehouse.warehouses.index')->with('success', __('Warehouse deleted.'));
    }
}
