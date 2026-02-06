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
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Support\Facades\Log;

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
            'products' => Product::where('is_active', true)
                ->with(['unit', 'stockBalances' => function ($q) {
                    $q->with('warehouse');
                }])
                ->orderBy('name_tr')
                ->get(),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()?->id;
        $validated['movement_date'] = $validated['movement_date'] ?? now('Europe/Istanbul');
        
        // Set unit_cost from product if not provided
        if (empty($validated['unit_cost'])) {
            $product = Product::find($validated['product_id']);
            $validated['unit_cost'] = $product?->unit_price;
        }
        
        $type = $validated['type'];

        if ($type === 'transfer') {
            $fromWarehouseId = $validated['from_warehouse_id'];
            $balanceFrom = StockBalance::firstOrCreate(
                ['warehouse_id' => $fromWarehouseId, 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balanceFrom->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('Insufficient stock in source warehouse.')])->withInput();
            }
            $balanceFrom->decrement('quantity', $validated['quantity']);
            $validated['from_warehouse_id'] = $fromWarehouseId;
        } elseif ($type === 'out') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balance->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('Insufficient stock.')])->withInput();
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

    /**
     * Return products available for a given warehouse (JSON).
     */
    public function productsByWarehouse(Request $request): JsonResponse
    {
        $warehouseId = (int) $request->query('warehouse_id');
        if (! $warehouseId) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->whereHas('stockBalances', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->with(['unit', 'stockBalances' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])
            ->orderBy('name_tr')
            ->get()
            ->map(function ($p) use ($warehouseId) {
                return [
                    'id' => $p->id,
                    'name_tr' => $p->name_tr,
                    'name_en' => $p->name_en,
                    'unit_price' => $p->unit_price,
                    'unit' => $p->unit,
                    'stock_quantity' => optional($p->stockBalances->first())->quantity ?? 0,
                ];
            });

        return response()->json($products);
    }

    public function edit(StockMovement $stock_movement): Response
    {
        $this->authorize('stock.movements.edit');

        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        $products = Product::where('is_active', true)
            ->with(['unit'])
            ->orderBy('name_tr')
            ->get();

        return Inertia::render('warehouse/stock-movements/Edit', [
            'movement' => $stock_movement->load(['product', 'warehouse', 'fromWarehouse', 'user']),
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(StoreStockMovementRequest $request, StockMovement $stock_movement): RedirectResponse
    {
        $this->authorize('stock.movements.edit');

        $validated = $request->validated();
        $validated['user_id'] = $request->user()?->id;
        $validated['movement_date'] = $validated['movement_date'] ?? now('Europe/Istanbul');

        if (empty($validated['unit_cost'])) {
            $product = Product::find($validated['product_id']);
            $validated['unit_cost'] = $product?->unit_price;
        }

        // Reverse previous movement effects
        $old = $stock_movement;
        try {
            if ($old->type === 'transfer') {
                // Add back to fromWarehouse, remove from to-warehouse
                $balanceFrom = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->from_warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                $balanceFrom->increment('quantity', $old->quantity);

                $balanceTo = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                if ((float) $balanceTo->quantity < (float) $old->quantity) {
                    return back()->withErrors(['movement' => __('Cannot reverse movement: insufficient stock in target warehouse.')])->withInput();
                }
                $balanceTo->decrement('quantity', $old->quantity);
            } elseif ($old->type === 'out') {
                $balance = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                $balance->increment('quantity', $old->quantity);
            } else { // in / adjustment
                $balance = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                if ((float) $balance->quantity < (float) $old->quantity) {
                    return back()->withErrors(['movement' => __('Cannot reverse movement: insufficient stock to remove.')])->withInput();
                }
                $balance->decrement('quantity', $old->quantity);
            }
        } catch (\Exception $e) {
            Log::error('Failed reversing old movement: ' . $e->getMessage());
            return back()->withErrors(['movement' => __('Failed to reverse previous movement.')])->withInput();
        }

        // Apply new movement effects (reuse logic from store)
        $type = $validated['type'];

        if ($type === 'transfer') {
            $fromWarehouseId = $validated['from_warehouse_id'];
            $balanceFrom = StockBalance::firstOrCreate(
                ['warehouse_id' => $fromWarehouseId, 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balanceFrom->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('Insufficient stock in source warehouse.')])->withInput();
            }
            $balanceFrom->decrement('quantity', $validated['quantity']);
        } elseif ($type === 'out') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balance->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('Insufficient stock.')])->withInput();
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

        $stock_movement->update($validated);

        ActivityLogger::log(
            'stock_update',
            __('Stock movement updated') . ': ' . ($validated['product_id'] ?? $stock_movement->product_id),
            $stock_movement,
            null,
            $validated,
            (int) ($validated['product_id'] ?? $stock_movement->product_id)
        );

        return redirect()->route('warehouse.stock-movements.index')->with('success', __('Stock movement updated.'));
    }

    public function destroy(StockMovement $stock_movement): RedirectResponse
    {
        $this->authorize('stock.movements.delete');

        $old = $stock_movement;

        try {
            if ($old->type === 'transfer') {
                $balanceFrom = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->from_warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                $balanceFrom->increment('quantity', $old->quantity);

                $balanceTo = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                if ((float) $balanceTo->quantity < (float) $old->quantity) {
                    return back()->withErrors(['movement' => __('Cannot reverse movement: insufficient stock in target warehouse.')]);
                }
                $balanceTo->decrement('quantity', $old->quantity);
            } elseif ($old->type === 'out') {
                $balance = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                $balance->increment('quantity', $old->quantity);
            } else { // in / adjustment
                $balance = StockBalance::firstOrCreate(
                    ['warehouse_id' => $old->warehouse_id, 'product_id' => $old->product_id],
                    ['quantity' => 0]
                );
                if ((float) $balance->quantity < (float) $old->quantity) {
                    return back()->withErrors(['movement' => __('Cannot reverse movement: insufficient stock to remove.')]);
                }
                $balance->decrement('quantity', $old->quantity);
            }
        } catch (\Exception $e) {
            Log::error('Failed reversing movement on delete: ' . $e->getMessage());
            return back()->withErrors(['movement' => __('Failed to reverse movement.')]);
        }

        ActivityLogger::log('stock_delete', __('Stock movement deleted') . ': ' . $old->id, $old);

        $old->delete();

        return redirect()->route('warehouse.stock-movements.index')->with('success', __('Stock movement deleted.'));
    }
}
