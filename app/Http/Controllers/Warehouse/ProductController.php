<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreProductRequest;
use App\Http\Requests\Warehouse\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Services\ActivityLogger;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Exports\ProductsExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('products.view');

        [$movementDateFrom, $movementDateTo] = $this->getMovementDateRange($request);
        $warehouseId = $request->input('warehouse_id');

        $query = Product::query()
            ->with(['category', 'unit', 'stockBalances.warehouse']);

        $this->applyProductFilters($query, $request, $movementDateFrom, $movementDateTo, $warehouseId);
        $this->applyMovementRangeRelationLoad($query, $movementDateFrom, $movementDateTo, $warehouseId);

        $products = $query
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15, ['*'], 'page', null)
            ->withQueryString()
            ->setPath(route('warehouse.products.index'));

        $this->appendDerivedProductFields($products->getCollection(), $movementDateFrom, $movementDateTo, $warehouseId);

        $categories = ProductCategory::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('warehouse/products/Index', [
            'products' => $products,
            'categories' => $categories,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('products.create');

        return Inertia::render('warehouse/products/Create', [
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'units' => Unit::where('is_active', true)->orderBy('sort_order')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $initialStock = $validated['initial_stock'] ?? 0;
        $hasStockBalances = array_key_exists('stock_balances', $validated);
        $stockBalances = $validated['stock_balances'] ?? null;
        unset($validated['initial_stock']);
        unset($validated['stock_balances']);

        $product = Product::create($validated);

        if ($hasStockBalances) {
            if (is_array($stockBalances)) {
                foreach ($stockBalances as $row) {
                    $warehouseId = (int) ($row['warehouse_id'] ?? 0);
                    if (!$warehouseId) {
                        continue;
                    }

                    StockBalance::updateOrCreate([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                    ], ['quantity' => (float) ($row['quantity'] ?? 0)]);
                }
            }
        } else {
            // Backward-compatible: if a warehouse was provided, create/update stock balance with initial stock
            if ($request->filled('warehouse_id')) {
                StockBalance::updateOrCreate([
                    'warehouse_id' => $request->input('warehouse_id'),
                    'product_id' => $product->id,
                ], ['quantity' => $initialStock]);
            }
        }

        ActivityLogger::log('product.create', __('Product created'), $product, null, $product->toArray(), $product->id);

        return redirect()->route('warehouse.products.index')->with('success', __('Product created.'));
    }

    public function show(Product $product): Response
    {
        $this->authorize('products.view');

        $product->load(['category', 'unit']);

        $logs = \App\Models\ActivityLog::query()
            ->with('user:id,name,email')
            ->where('product_id', $product->id)
            ->latest()
            ->limit(50)
            ->get();

        $stockBalances = \App\Models\StockBalance::query()
            ->with('warehouse')
            ->where('product_id', $product->id)
            ->get();

        return Inertia::render('warehouse/products/Show', [
            'product' => $product,
            'logs' => $logs,
            'stockBalances' => $stockBalances,
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('products.edit');

        // Load relationships including existing stock balances
        $product->load(['category', 'unit', 'stockBalances.warehouse']);

        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        $stockBalances = $product->stockBalances
            ->map(fn (StockBalance $b) => [
                'warehouse_id' => (int) $b->warehouse_id,
                'quantity' => (float) $b->quantity,
            ])
            ->values();

        return Inertia::render('warehouse/products/Edit', [
            'product' => $product,
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'units' => Unit::where('is_active', true)->orderBy('sort_order')->get(),
            'warehouses' => $warehouses,
            'stock_balances' => $stockBalances,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->toArray();
        $validated = $request->validated();
        $initialStock = $validated['initial_stock'] ?? null;
        $hasStockBalances = array_key_exists('stock_balances', $validated);
        $stockBalances = $validated['stock_balances'] ?? null;
        unset($validated['initial_stock']);
        unset($validated['stock_balances']);

        // Check if price changed before updating
        $oldPrice = $product->unit_price !== null ? (float) $product->unit_price : null;
        $newPrice = array_key_exists('unit_price', $validated) && $validated['unit_price'] !== null
            ? (float) $validated['unit_price']
            : $oldPrice;
        $priceChanged = $oldPrice !== $newPrice;

        $product->update($validated);

        // Log price change to history if price was updated
        if ($priceChanged && $newPrice !== null) {
            $historyData = [
                'product_id' => $product->id,
                'reason' => 'Manual price update',
            ];

            // Support both old schema (`price`) and new schema (`previous_price` + `new_price`).
            if (Schema::hasColumn('product_price_histories', 'new_price')) {
                $historyData['previous_price'] = $oldPrice;
                $historyData['new_price'] = $newPrice;
            } else {
                $historyData['price'] = $newPrice;
            }

            ProductPriceHistory::create($historyData);
        }

        if ($hasStockBalances) {
            DB::transaction(function () use ($stockBalances, $product) {
                $warehouseIds = [];

                if (is_array($stockBalances)) {
                    foreach ($stockBalances as $row) {
                        $warehouseId = (int) ($row['warehouse_id'] ?? 0);
                        if (!$warehouseId) {
                            continue;
                        }

                        $warehouseIds[] = $warehouseId;

                        StockBalance::updateOrCreate([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $product->id,
                        ], ['quantity' => (float) ($row['quantity'] ?? 0)]);
                    }
                }

                StockBalance::where('product_id', $product->id)
                    ->when(
                        count($warehouseIds),
                        fn ($q) => $q->whereNotIn('warehouse_id', array_values(array_unique($warehouseIds))),
                    )
                    ->delete();
            });
        } else {
            // Backward-compatible: if a warehouse was provided, create/update stock balance with initial stock
            if ($request->filled('warehouse_id')) {
                if ($initialStock !== null) {
                    StockBalance::updateOrCreate([
                        'warehouse_id' => $request->input('warehouse_id'),
                        'product_id' => $product->id,
                    ], ['quantity' => $initialStock]);
                } else {
                    StockBalance::firstOrCreate([
                        'warehouse_id' => $request->input('warehouse_id'),
                        'product_id' => $product->id,
                    ], ['quantity' => 0]);
                }
            }
        }

        ActivityLogger::log('product.update', __('Product updated'), $product, $old, $product->fresh()->toArray(), $product->id);

        return redirect()->route('warehouse.products.index')->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('products.delete');

        $data = $product->toArray();
        $product->delete();

        ActivityLogger::log('product.delete', __('Product deleted'), null, $data, null, $data['id'] ?? null);

        return redirect()->route('warehouse.products.index')->with('success', __('Product deleted.'));
    }

    /**
     * Search products by name (for autocomplete/searchable selects)
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = $request->query('q', '');
        $warehouseId = $request->query('warehouse_id');

        $query = Product::where('is_active', true);

        if (!empty($q)) {
            $query->where(function ($query) use ($q) {
                $query->where('name_tr', 'ilike', "%{$q}%")
                    ->orWhere('name_en', 'ilike', "%{$q}%")
                    ->orWhere('sku', 'ilike', "%{$q}%")
                    ->orWhere('barcode', 'ilike', "%{$q}%");
            });
        }

        if ($warehouseId) {
            $query->whereHas('stockBalances', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        $productsQuery = $query
            ->with(['unit', 'category', 'stockBalances' => function ($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            }])
            ->orderBy('name_tr');

        if (!$warehouseId) {
            $productsQuery->limit(50);
        }

        $products = $productsQuery
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name_tr' => $p->name_tr,
                'name_en' => $p->name_en,
                'sku' => $p->sku,
                'unit_price' => $p->unit_price,
                'unit' => $p->unit,
                'category' => $p->category,
                'stock_quantity' => optional($p->stockBalances->first())->quantity ?? 0,
            ]);

        return response()->json($products);
    }

    /**
     * Return stock quantity for a given product and warehouse (JSON).
     */
    public function stock(Product $product, Request $request): \Illuminate\Http\JsonResponse
    {
        $warehouseId = (int) $request->query('warehouse_id');
        if (!$warehouseId) {
            return response()->json(['quantity' => 0]);
        }

        $balance = \App\Models\StockBalance::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return response()->json(['quantity' => (float) ($balance?->quantity ?? 0)]);
    }

    /**
     * Export products to Excel
     */
    public function exportExcel(Request $request)
    {
        $this->authorize('products.view');

        $products = $this->getProductsForExport($request);
        
        Log::info('Export Excel - Filtered products count: ' . $products->count());
        
        // Create Excel export
        return Excel::download(
            new ProductsExport($products),
            'products-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    /**
     * Export products to PDF
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('products.view');

        $products = $this->getProductsForExport($request);
        
        Log::info('Export PDF - Filtered products count: ' . $products->count());
        
        $locale = app()->getLocale();
        $pdf = Pdf::loadView('exports.products-pdf', [
            'products' => $products,
            'locale' => $locale,
        ]);

        if ($request->boolean('print')) {
            return $pdf->stream('products-' . now()->format('Y-m-d-H-i-s') . '.pdf');
        }

        return $pdf->download('products-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('products.delete');

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $deletedCount = 0;
        foreach ($validated['product_ids'] as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $data = $product->toArray();
                $product->delete();
                ActivityLogger::log('product.delete', __('Product deleted'), null, $data, null, $data['id'] ?? null);
                $deletedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('bulk.deleted', ['count' => $deletedCount]),
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Get filtered products based on request
     */
    private function getFilteredProducts(Request $request)
    {
        [$movementDateFrom, $movementDateTo] = $this->getMovementDateRange($request);
        $warehouseId = $request->input('warehouse_id');

        $query = Product::query()
            ->with(['category', 'unit', 'stockBalances']);

        $this->applyProductFilters($query, $request, $movementDateFrom, $movementDateTo, $warehouseId);
        $this->applyMovementRangeRelationLoad($query, $movementDateFrom, $movementDateTo, $warehouseId);

        $products = $query
            ->orderBy('sort_order')
            ->orderBy('name_tr');
        
        Log::info('Product Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        $results = $products->get();
        $this->appendDerivedProductFields($results, $movementDateFrom, $movementDateTo, $warehouseId);

        return $results;
    }

    /**
     * Get products for export (from selected IDs or filters)
     */
    private function getProductsForExport(Request $request)
    {
        $rawProductIds = $request->input('product_ids');
        if ($rawProductIds !== null && !is_array($rawProductIds)) {
            $rawProductIds = [$rawProductIds];
        }

        $productIds = array_values(array_filter((array) $rawProductIds, fn ($v) => $v !== null && $v !== ''));
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        // If specific product IDs are provided, use them
        if (count($productIds) > 0) {
            Log::info('Export - Using selected product IDs:', [
                'count' => count($productIds),
                'ids' => $productIds,
            ]);

            return Product::query()
                ->with(['category', 'unit', 'stockBalances.warehouse'])
                ->whereIn('id', $productIds)
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->get();
        }

        // Otherwise, use filters
        Log::info('Export - Using filters:', [
            'search' => $request->search,
            'category_id' => $request->category_id,
            'is_active' => $request->is_active,
            'warehouse_id' => $request->warehouse_id,
            'movement_date_from' => $request->movement_date_from,
            'movement_date_to' => $request->movement_date_to,
        ]);

        return $this->getFilteredProducts($request);
    }

    private function getMovementDateRange(Request $request): array
    {
        return [
            $request->input('movement_date_from'),
            $request->input('movement_date_to'),
        ];
    }

    private function applyProductFilters(Builder $query, Request $request, ?string $movementDateFrom, ?string $movementDateTo, mixed $warehouseId): void
    {
        $query
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name_tr', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%")
                    ->orWhere('barcode', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($warehouseId, fn ($q) => $q->whereHas('stockBalances', fn ($sq) => $sq->where('warehouse_id', $warehouseId)))
            ->when($movementDateFrom || $movementDateTo, function ($q) use ($movementDateFrom, $movementDateTo, $warehouseId) {
                $q->whereHas('stockMovements', function ($mq) use ($movementDateFrom, $movementDateTo, $warehouseId) {
                    $mq->when($movementDateFrom, fn ($inner) => $inner->whereDate('movement_date', '>=', $movementDateFrom))
                        ->when($movementDateTo, fn ($inner) => $inner->whereDate('movement_date', '<=', $movementDateTo))
                        ->when($warehouseId, function ($inner) use ($warehouseId) {
                            $inner->where(function ($warehouseQuery) use ($warehouseId) {
                                $warehouseQuery
                                    ->where('warehouse_id', $warehouseId)
                                    ->orWhere('from_warehouse_id', $warehouseId);
                            });
                        });
                });
            });
    }

    private function applyMovementRangeRelationLoad(Builder $query, ?string $movementDateFrom, ?string $movementDateTo, mixed $warehouseId): void
    {
        if (!$movementDateFrom && !$movementDateTo) {
            return;
        }

        $query->with(['stockMovements' => function ($q) use ($movementDateFrom, $movementDateTo, $warehouseId) {
            $q->select(['id', 'product_id', 'type', 'movement_date'])
                ->when($movementDateFrom, fn ($inner) => $inner->whereDate('movement_date', '>=', $movementDateFrom))
                ->when($movementDateTo, fn ($inner) => $inner->whereDate('movement_date', '<=', $movementDateTo))
                ->when($warehouseId, function ($inner) use ($warehouseId) {
                    $inner->where(function ($warehouseQuery) use ($warehouseId) {
                        $warehouseQuery
                            ->where('warehouse_id', $warehouseId)
                            ->orWhere('from_warehouse_id', $warehouseId);
                    });
                })
                ->orderByDesc('movement_date');
        }]);
    }

    private function appendDerivedProductFields($products, ?string $movementDateFrom, ?string $movementDateTo, mixed $warehouseId = null): void
    {
        $products->transform(function ($product) use ($movementDateFrom, $movementDateTo, $warehouseId) {
            if ($warehouseId) {
                $product->stock_quantity = $product->stockBalances
                    ->where('warehouse_id', (int) $warehouseId)
                    ->sum('quantity') ?? 0;
            } else {
                $product->stock_quantity = $product->stockBalances->sum('quantity') ?? 0;
            }

            if ($movementDateFrom || $movementDateTo) {
                $movements = $product->stockMovements ?? collect();
                $lastMovementDate = $movements->first()?->movement_date;

                $product->movement_stats = [
                    'count' => $movements->count(),
                    'in' => $movements->where('type', 'in')->count(),
                    'out' => $movements->where('type', 'out')->count(),
                    'transfer' => $movements->where('type', 'transfer')->count(),
                    'adjustment' => $movements->where('type', 'adjustment')->count(),
                    'last_date' => $lastMovementDate ? (string) $lastMovementDate : null,
                ];
            }

            return $product;
        });
    }

    /**
     * Get product price history
     */
    public function priceHistory(Product $product): \Illuminate\Http\JsonResponse
    {
        $this->authorize('products.view');

        $historyQuery = $product->priceHistories();
        if (Schema::hasColumn('product_price_histories', 'new_price')) {
            $historyQuery->select(['id', 'previous_price', 'new_price', 'reason', 'created_at']);
        } else {
            $historyQuery->select([
                'id',
                DB::raw('NULL as previous_price'),
                DB::raw('price as new_price'),
                'reason',
                'created_at',
            ]);
        }

        $history = $historyQuery->get();

        return response()->json([
            'product_id' => $product->id,
            'current_price' => $product->unit_price,
            'history' => $history,
        ]);
    }

    public function duplicateNames(Request $request): JsonResponse
    {
        $this->authorize('products.view');

        $products = Product::query()
            ->select(['id', 'name_tr', 'name_en', 'sku'])
            ->with(['stockBalances:product_id,warehouse_id,quantity'])
            ->get();

        $normalize = static fn (mixed $value): string => mb_strtolower(trim((string) ($value ?? '')));

        $dupeTr = $products
            ->groupBy(fn (Product $p) => $normalize($p->name_tr))
            ->filter(fn ($group, string $key) => $key !== '' && $group->count() > 1)
            ->map(fn ($group, string $key) => [
                'key' => $group->first()?->name_tr ?? $key,
                'products' => $group->map(fn (Product $p) => [
                    'id' => (int) $p->id,
                    'sku' => $p->sku,
                    'name_tr' => (string) $p->name_tr,
                    'name_en' => (string) $p->name_en,
                    'stock_balances' => $p->stockBalances
                        ->map(fn (StockBalance $b) => [
                            'warehouse_id' => (int) $b->warehouse_id,
                            'quantity' => (float) $b->quantity,
                        ])
                        ->values(),
                ])->values(),
            ])
            ->values();

        $dupeEn = $products
            ->groupBy(fn (Product $p) => $normalize($p->name_en))
            ->filter(fn ($group, string $key) => $key !== '' && $group->count() > 1)
            ->map(fn ($group, string $key) => [
                'key' => $group->first()?->name_en ?? $key,
                'products' => $group->map(fn (Product $p) => [
                    'id' => (int) $p->id,
                    'sku' => $p->sku,
                    'name_tr' => (string) $p->name_tr,
                    'name_en' => (string) $p->name_en,
                    'stock_balances' => $p->stockBalances
                        ->map(fn (StockBalance $b) => [
                            'warehouse_id' => (int) $b->warehouse_id,
                            'quantity' => (float) $b->quantity,
                        ])
                        ->values(),
                ])->values(),
            ])
            ->values();

        return response()->json([
            'name_tr' => $dupeTr,
            'name_en' => $dupeEn,
        ]);
    }

    public function mergeDuplicates(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('products.delete');

        $validated = $request->validate([
            'keep_product_id' => 'required|integer|exists:products,id',
            'remove_product_ids' => 'required|array|min:1',
            'remove_product_ids.*' => 'required|integer|distinct|exists:products,id',
        ]);

        $keepProductId = (int) $validated['keep_product_id'];
        $removeProductIds = array_values(array_unique(array_map('intval', $validated['remove_product_ids'])));
        $removeProductIds = array_values(array_filter($removeProductIds, fn (int $id) => $id !== $keepProductId));

        if (count($removeProductIds) === 0) {
            return response()->json(['ok' => true, 'merged' => 0]);
        }

        DB::transaction(function () use ($keepProductId, $removeProductIds) {
            $keep = Product::query()->whereKey($keepProductId)->lockForUpdate()->firstOrFail();

            $removeProducts = Product::query()
                ->whereIn('id', $removeProductIds)
                ->lockForUpdate()
                ->get();

            $replaceProductIdsDeep = function (mixed $value, int $fromId, int $toId) use (&$replaceProductIdsDeep) {
                if (is_array($value)) {
                    $out = [];
                    foreach ($value as $k => $v) {
                        if ($k === 'product_id' && (string) $v === (string) $fromId) {
                            $out[$k] = $toId;
                            continue;
                        }
                        $out[$k] = $replaceProductIdsDeep($v, $fromId, $toId);
                    }
                    return $out;
                }

                return $value;
            };

            foreach ($removeProducts as $remove) {
                // Merge stock balances by warehouse (sum quantity + reserved_quantity)
                $balances = StockBalance::query()
                    ->where('product_id', $remove->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($balances as $b) {
                    $existing = StockBalance::query()
                        ->where('product_id', $keep->id)
                        ->where('warehouse_id', $b->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'quantity' => (float) $existing->quantity + (float) $b->quantity,
                            'reserved_quantity' => (float) $existing->reserved_quantity + (float) $b->reserved_quantity,
                        ]);
                        $b->delete();
                    } else {
                        $b->update(['product_id' => $keep->id]);
                    }
                }

                // Re-point related records to kept product
                StockMovement::where('product_id', $remove->id)->update(['product_id' => $keep->id]);
                DB::table('purchase_order_items')->where('product_id', $remove->id)->update(['product_id' => $keep->id]);
                DB::table('product_price_histories')->where('product_id', $remove->id)->update(['product_id' => $keep->id]);
                DB::table('activity_logs')->where('product_id', $remove->id)->update(['product_id' => $keep->id]);
                DB::table('activity_logs')
                    ->where('subject_type', Product::class)
                    ->where('subject_id', $remove->id)
                    ->update(['subject_id' => $keep->id, 'product_id' => $keep->id]);

                // Best-effort: also update old/new JSON snapshots that contain the removed product id.
                $candidates = ActivityLog::query()
                    ->where(function ($q) use ($remove) {
                        $q->where('new_values', 'like', '%"product_id":' . $remove->id . '%')
                            ->orWhere('new_values', 'like', '%"product_id":"' . $remove->id . '"%')
                            ->orWhere('old_values', 'like', '%"product_id":' . $remove->id . '%')
                            ->orWhere('old_values', 'like', '%"product_id":"' . $remove->id . '"%');
                    })
                    ->lockForUpdate()
                    ->get(['id', 'old_values', 'new_values']);

                foreach ($candidates as $log) {
                    $changed = false;
                    $oldValues = $log->old_values;
                    $newValues = $log->new_values;

                    if (is_array($oldValues)) {
                        $old2 = $replaceProductIdsDeep($oldValues, (int) $remove->id, (int) $keep->id);
                        if ($old2 !== $oldValues) {
                            $oldValues = $old2;
                            $changed = true;
                        }
                    }
                    if (is_array($newValues)) {
                        $new2 = $replaceProductIdsDeep($newValues, (int) $remove->id, (int) $keep->id);
                        if ($new2 !== $newValues) {
                            $newValues = $new2;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $log->old_values = $oldValues;
                        $log->new_values = $newValues;
                        $log->save();
                    }
                }

                // Handle restaurant menu ingredients unique constraint (menu_item_id + product_id)
                $ingredients = DB::table('restaurant_menu_item_ingredients')
                    ->where('product_id', $remove->id)
                    ->lockForUpdate()
                    ->get(['id', 'restaurant_menu_item_id', 'quantity']);

                foreach ($ingredients as $row) {
                    $existingIngredient = DB::table('restaurant_menu_item_ingredients')
                        ->where('restaurant_menu_item_id', $row->restaurant_menu_item_id)
                        ->where('product_id', $keep->id)
                        ->lockForUpdate()
                        ->first(['id', 'quantity']);

                    if ($existingIngredient) {
                        DB::table('restaurant_menu_item_ingredients')
                            ->where('id', $existingIngredient->id)
                            ->update([
                                'quantity' => (float) $existingIngredient->quantity + (float) $row->quantity,
                                'updated_at' => now(),
                            ]);
                        DB::table('restaurant_menu_item_ingredients')->where('id', $row->id)->delete();
                    } else {
                        DB::table('restaurant_menu_item_ingredients')
                            ->where('id', $row->id)
                            ->update([
                                'product_id' => $keep->id,
                                'updated_at' => now(),
                            ]);
                    }
                }

                $remove->delete();
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'merged' => count($removeProductIds)]);
        }

        return back()->with('success', __('Product updated.'));
    }

}
