<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreUnitRequest;
use App\Http\Requests\Warehouse\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('units.view');

        $units = Unit::with('baseUnit')
            ->withCount('products')
            ->when($request->search, fn ($q) => $q->where('name_tr', 'like', "%{$request->search}%")
                ->orWhere('name_en', 'like', "%{$request->search}%")
                ->orWhere('symbol', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('warehouse/units/Index', [
            'units' => $units,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('units.create');

        return Inertia::render('warehouse/units/Create', [
            'baseUnits' => Unit::whereNull('base_unit_id')->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        ActivityLogger::log('unit.create', __('Unit created'), $unit, null, $unit->toArray());

        return redirect()->route('warehouse.units.index')->with('success', __('Unit created.'));
    }

    public function edit(Unit $unit): Response
    {
        $this->authorize('units.edit');

        return Inertia::render('warehouse/units/Edit', [
            'unit' => $unit,
            'baseUnits' => Unit::whereNull('base_unit_id')->where('is_active', true)->where('id', '!=', $unit->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('warehouse.units.index')->with('success', __('Unit updated.'));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('units.delete');

        if ($unit->products()->exists()) {
            return back()->with('error', __('Unit has products and cannot be deleted.'));
        }

        $data = $unit->toArray();
        $unit->delete();

        ActivityLogger::log('unit.delete', __('Unit deleted'), null, $data, null);

        return redirect()->route('warehouse.units.index')->with('success', __('Unit deleted.'));
    }
}
