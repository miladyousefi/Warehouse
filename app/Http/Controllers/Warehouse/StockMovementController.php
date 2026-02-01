<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreStockMovementRequest;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('stock.movements.view');

        $movements = StockMovement::query()
            ->with(['product', 'warehouse', 'fromWarehouse', 'user'])
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->date_from, fn ($q) => $q->whereDate('movement_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('movement_date', '<=', $request->date_to))
            ->latest('movement_date')
            ->paginate(20)
            ->withQueryString();

        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('warehouse/stock-movements/Index', [
            'movements' => $movements,
            'warehouses' => $warehouses,
        ]);
    }

    public function create(Request $request): Response
    {
        $type = $request->get('type', 'in');
        $permission = match ($type) {
            'out' => 'stock.out',
            'transfer' => 'stock.transfer',
            'adjustment' => 'stock.adjustment',
            default => 'stock.in',
        };
        $this->authorize($permission);

        return Inertia::render('warehouse/stock-movements/Create', [
            'type' => $type,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name_tr')->get(),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()?->id;
        $validated['movement_date'] = $validated['movement_date'] ?? now('Europe/Istanbul');
        $type = $validated['type'];

        if ($type === 'transfer') {
            $fromWarehouseId = $validated['from_warehouse_id'];
            $balanceFrom = StockBalance::firstOrCreate(
                ['warehouse_id' => $fromWarehouseId, 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balanceFrom->quantity < (float) $validated['quantity']) {
                return back()->with('error', __('Insufficient stock in source warehouse.'));
            }
            $balanceFrom->decrement('quantity', $validated['quantity']);
            $validated['from_warehouse_id'] = $fromWarehouseId;
        } elseif ($type === 'out') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balance->quantity < (float) $validated['quantity']) {
                return back()->with('error', __('Insufficient stock.'));
            }
            $balance->decrement('quantity', $validated['quantity']);
        }

        if ($type === 'in' || $type === 'transfer' || $type === 'adjustment') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            $balance->increment('quantity', $validated['quantity']);
        }

        if ($type === 'adjustment') {
            // Adjustment can be negative - we already incremented; for negative adjustment we'd need to pass negative qty and handle
            // For simplicity we support only positive adjustment here; negative would be a separate "out" type or negative qty
        }

        $movement = StockMovement::create($validated);

        $productName = Product::find($validated['product_id'])?->name_tr ?? '';
        ActivityLogger::log(
            'stock_' . $type,
            __('Stock :type recorded', ['type' => $type]) . ': ' . $productName . ' x ' . $validated['quantity'],
            $movement,
            null,
            $validated,
            (int) $validated['product_id']
        );

        return redirect()->route('warehouse.stock-movements.index')->with('success', __('Stock movement recorded.'));
    }
}
