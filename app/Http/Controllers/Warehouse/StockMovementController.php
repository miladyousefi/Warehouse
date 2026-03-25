<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
/** @noinspection PhpUndefinedClassInspection */
use Maatwebsite\Excel\Facades\Excel;
/** @noinspection PhpUndefinedClassInspection */
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\StockMovementsExport;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('stock_movements.view');

        $movements = $this->buildFilteredMovementsQuery($request)
            ->paginate(20)
            ->withQueryString()
            ->setPath('/warehouse/stock-movements');

        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('warehouse/stock-movements/Index', [
            'movements' => $movements,
            'warehouses' => $warehouses,
            'suppliers' => $suppliers,
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
                ->with([
                    'unit',
                    'stockBalances' => function ($q) {
                        $q->with('warehouse');
                    }
                ])
                ->orderBy('name_tr')
                ->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()?->id;
        $validated['movement_date'] = $validated['movement_date'] ?? now('Europe/Istanbul');

        $type = $validated['type'];

        // Pre-validate all products exist when handling rows or single item
        $productIds = [];
        if (!empty($validated['rows']) && is_array($validated['rows'])) {
            $productIds = array_column($validated['rows'], 'product_id');
        } elseif (!empty($validated['product_id'])) {
            $productIds = [$validated['product_id']];
        }

        if (!empty($productIds)) {
            $foundProducts = Product::whereIn('id', array_filter($productIds))->pluck('id')->toArray();
            $missingProducts = array_diff(array_filter($productIds), $foundProducts);
            if (!empty($missingProducts)) {
                return back()->withErrors(['product_id' => __('stockMovements.productNotFound')])->withInput();
            }
        }

        // If rows array provided, create one StockMovement per row and update balances per item
        if (!empty($validated['rows']) && is_array($validated['rows'])) {
            try {
                DB::transaction(function () use ($validated, $type) {
                    $rows = $validated['rows'];
                    $productMap = Product::whereIn('id', array_column($rows, 'product_id'))->get()->keyBy('id');

                    $requiredOut = [];
                    $requiredTransfer = [];

                    foreach ($rows as $rowIndex => $row) {
                        $rowProductId = (int) $row['product_id'];
                        $rowQuantity = (float) ($row['quantity'] ?? 0);

                        if (!$productMap->has($rowProductId)) {
                            throw ValidationException::withMessages([
                                "rows.$rowIndex.product_id" => __('stockMovements.productNotFound'),
                            ]);
                        }

                        if ($type === 'out') {
                            $warehouseId = (int) $row['warehouse_id'];
                            $requiredOut["{$warehouseId}:{$rowProductId}"] = ($requiredOut["{$warehouseId}:{$rowProductId}"] ?? 0) + $rowQuantity;
                        }

                        if ($type === 'transfer') {
                            $fromWarehouseId = (int) ($row['from_warehouse_id'] ?? ($validated['from_warehouse_id'] ?? 0));
                            if ($rowQuantity <= 0) {
                                $fromBalance = StockBalance::where('warehouse_id', $fromWarehouseId)
                                    ->where('product_id', $rowProductId)
                                    ->lockForUpdate()
                                    ->first();
                                $rowQuantity = (float) ($fromBalance?->quantity ?? 0);
                                $rows[$rowIndex]['quantity'] = $rowQuantity;
                            }
                            $requiredTransfer["{$fromWarehouseId}:{$rowProductId}"] = ($requiredTransfer["{$fromWarehouseId}:{$rowProductId}"] ?? 0) + $rowQuantity;
                        }
                    }

                    // Validate all affected stock balances before any write for multi-row out/transfer
                    foreach ($requiredOut as $key => $requiredQty) {
                        [$warehouseId, $productId] = array_map('intval', explode(':', $key, 2));
                        $balance = StockBalance::where('warehouse_id', $warehouseId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();

                        $available = (float) ($balance?->quantity ?? 0);
                        if ($available < (float) $requiredQty) {
                            throw ValidationException::withMessages([
                                'rows.0.quantity' => __('stockMovements.insufficientStock'),
                            ]);
                        }
                    }

                    foreach ($requiredTransfer as $key => $requiredQty) {
                        [$fromWarehouseId, $productId] = array_map('intval', explode(':', $key, 2));
                        $balance = StockBalance::where('warehouse_id', $fromWarehouseId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();

                        $available = (float) ($balance?->quantity ?? 0);
                        if ($available < (float) $requiredQty) {
                            throw ValidationException::withMessages([
                                'rows.0.quantity' => __('stockMovements.insufficientStock'),
                            ]);
                        }
                    }

                    foreach ($rows as $row) {
                        $item = [
                            'type' => $type,
                            'user_id' => $validated['user_id'],
                            'movement_date' => $validated['movement_date'],
                            'notes' => $validated['notes'] ?? null,
                            'supplier_id' => $validated['supplier_id'] ?? null,
                            'factor_number' => $validated['factor_number'] ?? null,
                            'product_id' => $row['product_id'],
                            'warehouse_id' => $row['warehouse_id'],
                            'quantity' => $row['quantity'],
                            'from_warehouse_id' => $row['from_warehouse_id'] ?? ($validated['from_warehouse_id'] ?? null),
                            'unit_cost' => $row['unit_cost'] ?? null,
                        ];

                        $product = $productMap->get((int) $item['product_id']);

                        if (empty($item['unit_cost'])) {
                            $item['unit_cost'] = $product?->unit_price;
                        }

                        if ($type === 'in' && $product) {
                            $this->syncIncomingUnitPrice($product, $item['unit_cost']);
                        }

                        if ($type === 'transfer') {
                            $fromWarehouseId = (int) $item['from_warehouse_id'];
                            $balanceFrom = StockBalance::firstOrCreate(
                                ['warehouse_id' => $fromWarehouseId, 'product_id' => $item['product_id']],
                                ['quantity' => 0]
                            );
                            $balanceFrom->decrement('quantity', $item['quantity']);
                            if ($product) {
                                $product->update(['warehouse_id' => (int) $item['warehouse_id']]);
                            }
                        } elseif ($type === 'out') {
                            $balance = StockBalance::firstOrCreate(
                                ['warehouse_id' => $item['warehouse_id'], 'product_id' => $item['product_id']],
                                ['quantity' => 0]
                            );
                            $balance->decrement('quantity', $item['quantity']);
                        }

                        if ($type === 'in' || $type === 'transfer' || $type === 'adjustment') {
                            $balance = StockBalance::firstOrCreate(
                                ['warehouse_id' => $item['warehouse_id'], 'product_id' => $item['product_id']],
                                ['quantity' => 0]
                            );
                            $balance->increment('quantity', $item['quantity']);
                        }

                        $movement = StockMovement::create($item);

                        ActivityLogger::log(
                            'stock_' . $type,
                            __('Stock :type recorded', ['type' => $type]) . ': ' . ($product?->name_tr ?? '') . ' x ' . $item['quantity'],
                            $movement,
                            null,
                            $item,
                            (int) $item['product_id']
                        );
                    }
                });
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }

            return redirect()->route('warehouse.stock-movements.index')->with('success', __('Stock movements recorded.'));
        }

        // Fallback: single movement (existing behavior)
        if (empty($validated['unit_cost'])) {
            $product = Product::find($validated['product_id']);
            if (!$product) {
                return back()->withErrors(['product_id' => __('stockMovements.productNotFound')])->withInput();
            }
            $validated['unit_cost'] = $product->unit_price;
        }

        if ($type === 'in') {
            $product = Product::find($validated['product_id']);
            if ($product) {
                $this->syncIncomingUnitPrice($product, $validated['unit_cost']);
            }
        }

        if ($type === 'transfer') {
            $fromWarehouseId = $validated['from_warehouse_id'];
            $balanceFrom = StockBalance::firstOrCreate(
                ['warehouse_id' => $fromWarehouseId, 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if (empty($validated['quantity']) || (float) $validated['quantity'] <= 0) {
                $validated['quantity'] = (float) $balanceFrom->quantity;
            }
            if ((float) $balanceFrom->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('stockMovements.insufficientStock')])->withInput();
            }
            $balanceFrom->decrement('quantity', $validated['quantity']);
            $validated['from_warehouse_id'] = $fromWarehouseId;
            Product::where('id', $validated['product_id'])->update(['warehouse_id' => (int) $validated['warehouse_id']]);
        } elseif ($type === 'out') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balance->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('stockMovements.insufficientStock')])->withInput();
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

    private function syncIncomingUnitPrice(Product $product, mixed $rowUnitCost): void
    {
        if ($rowUnitCost === null || $rowUnitCost === '') {
            return;
        }

        $oldPrice = $product->unit_price !== null ? (float) $product->unit_price : null;
        $newPrice = (float) $rowUnitCost;

        if ($oldPrice !== null && abs($oldPrice - $newPrice) < 0.000001) {
            return;
        }

        $product->update(['unit_price' => $newPrice]);

        $historyData = [
            'product_id' => $product->id,
            'reason' => 'Stock input price update',
        ];

        if (!Schema::hasTable('product_price_histories')) {
            return;
        }

        if (Schema::hasColumn('product_price_histories', 'new_price')) {
            $historyData['previous_price'] = $oldPrice;
            $historyData['new_price'] = $newPrice;
        } else {
            $historyData['price'] = $newPrice;
        }

        ProductPriceHistory::create($historyData);
    }

    /**
     * Return products available for a given warehouse (JSON).
     */
    public function productsByWarehouse(Request $request): JsonResponse
    {
        $warehouseId = (int) $request->query('warehouse_id');
        if (!$warehouseId) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->whereHas('stockBalances', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->with([
                'unit',
                'stockBalances' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)->with('warehouse');
                }
            ])
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
                    'stockBalances' => $p->stockBalances->toArray(),
                ];
            });

        return response()->json($products);
    }

    public function edit(StockMovement $stock_movement): Response
    {
        $this->authorize('update', $stock_movement);

        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        // Load current product and many others to ensure product is found
        $products = Product::where('is_active', true)
            ->with(['unit', 'stockBalances' => function ($q) {
                $q->with('warehouse');
            }])
            ->orderBy('name_tr')
            ->get();

        return Inertia::render('warehouse/stock-movements/Edit', [
            'movement' => $stock_movement->load(['product', 'warehouse', 'fromWarehouse', 'user']),
            'warehouses' => $warehouses,
            'products' => $products,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(StoreStockMovementRequest $request, StockMovement $stock_movement): RedirectResponse
    {
        $this->authorize('update', $stock_movement);

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
                return back()->withErrors(['quantity' => __('stockMovements.insufficientStock')])->withInput();
            }
            $balanceFrom->decrement('quantity', $validated['quantity']);
        } elseif ($type === 'out') {
            $balance = StockBalance::firstOrCreate(
                ['warehouse_id' => $validated['warehouse_id'], 'product_id' => $validated['product_id']],
                ['quantity' => 0]
            );
            if ((float) $balance->quantity < (float) $validated['quantity']) {
                return back()->withErrors(['quantity' => __('stockMovements.insufficientStock')])->withInput();
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
        $this->authorize('delete', $stock_movement);

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

    /**
     * Get filtered movements for export
     */
    private function getFilteredMovements(Request $request)
    {
        $query = $this->buildFilteredMovementsQuery($request);
        
        // Log the query for debugging
        Log::info('Stock Movement Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        return $query->get();
    }

    private function buildFilteredMovementsQuery(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return StockMovement::query()
            ->with(['product', 'warehouse', 'fromWarehouse', 'user', 'supplier'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner
                        ->where('factor_number', 'like', '%' . $search . '%')
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where('name_tr', 'like', '%' . $search . '%')
                                ->orWhere('name_en', 'like', '%' . $search . '%')
                                ->orWhere('sku', 'like', '%' . $search . '%')
                                ->orWhere('barcode', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->factor_number, fn($q) => $q->where('factor_number', 'like', '%' . $request->factor_number . '%'))
            ->when($request->date_from, fn($q) => $q->whereDate('movement_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('movement_date', '<=', $request->date_to))
            ->latest('movement_date');
    }

    /**
     * Export movements to Excel
     */
    public function exportExcel(Request $request)
    {
        $this->authorize('stock_movements.view');

        // Log the request parameters for debugging
        Log::info('Export Excel - Request parameters:', [
            'warehouse_id' => $request->warehouse_id,
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'factor_number' => $request->factor_number,
            'type' => $request->type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'all_params' => $request->all(),
        ]);

        $movements = $this->getFilteredMovements($request);
        
        // Log the filtered results
        Log::info('Export Excel - Filtered movements count: ' . $movements->count());
        
        // Create Excel export
        return Excel::download(
            new StockMovementsExport($movements),
            'stock-movements-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export movements to PDF
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('stock_movements.view');

        // Log the request parameters for debugging
        Log::info('Export PDF - Request parameters:', [
            'warehouse_id' => $request->warehouse_id,
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'factor_number' => $request->factor_number,
            'type' => $request->type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'all_params' => $request->all(),
        ]);

        $movements = $this->getFilteredMovements($request);
        
        // Log the filtered results
        Log::info('Export PDF - Filtered movements count: ' . $movements->count());
        
        $locale = app()->getLocale();
        $pdf = Pdf::loadView('exports.stock-movements-pdf', [
            'movements' => $movements,
            'locale' => $locale,
        ])->setPaper('a4', 'portrait');

        if ($request->boolean('print')) {
            return $pdf->stream('stock-movements-' . now()->format('Y-m-d-H-i-s') . '.pdf');
        }

        return $pdf->download('stock-movements-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }
}
