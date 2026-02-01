<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Models\Warehouse as WarehouseModel;
use App\Services\ActivityLogger;
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
        $warehouse = WarehouseModel::create($request->validated());

        ActivityLogger::log('warehouse.create', __('Warehouse created'), $warehouse, null, $warehouse->toArray());

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
        $old = $warehouse->toArray();
        $warehouse->update($request->validated());

        ActivityLogger::log('warehouse.update', __('Warehouse updated'), $warehouse, $old, $warehouse->fresh()->toArray());

        return redirect()->route('warehouse.warehouses.index')->with('success', __('Warehouse updated.'));
    }

    public function destroy(WarehouseModel $warehouse): RedirectResponse
    {
        $this->authorize('warehouses.delete');

        if ($warehouse->stockBalances()->exists() || $warehouse->stockMovements()->exists()) {
            return back()->with('error', __('Warehouse has stock data and cannot be deleted.'));
        }

        $data = $warehouse->toArray();
        $warehouse->delete();

        ActivityLogger::log('warehouse.delete', __('Warehouse deleted'), null, $data, null);

        return redirect()->route('warehouse.warehouses.index')->with('success', __('Warehouse deleted.'));
    }
}
