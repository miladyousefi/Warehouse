<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreProductRequest;
use App\Http\Requests\Warehouse\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Services\ActivityLogger;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\StockBalance;
use App\Exports\ProductsExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $query = Product::query()
            ->with(['category', 'unit', 'stockBalances']);

        $this->applyProductFilters($query, $request, $movementDateFrom, $movementDateTo);
        $this->applyMovementRangeRelationLoad($query, $movementDateFrom, $movementDateTo);

        $products = $query
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15, ['*'], 'page', null)
            ->withQueryString()
            ->setPath(route('warehouse.products.index'));

        $this->appendDerivedProductFields($products->getCollection(), $movementDateFrom, $movementDateTo);

        $categories = ProductCategory::where('is_active', true)->orderBy('sort_order')->get();

        return Inertia::render('warehouse/products/Index', [
            'products' => $products,
            'categories' => $categories,
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
        unset($validated['initial_stock']);

        $product = Product::create($validated);

        // If a warehouse was provided, create/update stock balance with initial stock
        if ($request->filled('warehouse_id')) {
            StockBalance::updateOrCreate([
                'warehouse_id' => $request->input('warehouse_id'),
                'product_id' => $product->id,
            ], ['quantity' => $initialStock]);
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

        // Determine selected warehouse: prefer product.warehouse_id, then first stockBalance's warehouse, then first warehouse
        $selectedWarehouseId = $product->warehouse_id;
        if (!$selectedWarehouseId && $product->stockBalances && $product->stockBalances->count()) {
            $selectedWarehouseId = $product->stockBalances->first()->warehouse_id;
        }
        if (!$selectedWarehouseId) {
            $selectedWarehouseId = $warehouses->first()?->id ?? null;
        }

        // Get current stock quantity for the selected warehouse (if any)
        $initialStock = 0;
        if ($selectedWarehouseId) {
            $balance = $product->stockBalances->firstWhere('warehouse_id', $selectedWarehouseId);
            $initialStock = $balance?->quantity ?? 0;
        }
        // Cast to float to avoid trailing decimal zeros from DB decimal string
        $initialStock = (float) $initialStock;

        return Inertia::render('warehouse/products/Edit', [
            'product' => $product,
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'units' => Unit::where('is_active', true)->orderBy('sort_order')->get(),
            'warehouses' => $warehouses,
            'selected_warehouse_id' => $selectedWarehouseId,
            'initial_stock' => $initialStock,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->toArray();
        $validated = $request->validated();
        $initialStock = $validated['initial_stock'] ?? null;
        unset($validated['initial_stock']);

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

        // If a warehouse was provided, create/update stock balance with initial stock
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

        $products = $query
            ->with(['unit', 'category', 'stockBalances' => function ($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            }])
            ->orderBy('name_tr')
            ->limit(50)
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

        $query = Product::query()
            ->with(['category', 'unit', 'stockBalances']);

        $this->applyProductFilters($query, $request, $movementDateFrom, $movementDateTo);
        $this->applyMovementRangeRelationLoad($query, $movementDateFrom, $movementDateTo);

        $products = $query
            ->orderBy('sort_order')
            ->orderBy('name_tr');
        
        Log::info('Product Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        $results = $products->get();
        $this->appendDerivedProductFields($results, $movementDateFrom, $movementDateTo);

        return $results;
    }

    /**
     * Get products for export (from selected IDs or filters)
     */
    private function getProductsForExport(Request $request)
    {
        // If specific product IDs are provided, use them
        if ($request->has('product_ids') && is_array($request->product_ids) && count($request->product_ids) > 0) {
            Log::info('Export - Using selected product IDs:', [
                'count' => count($request->product_ids),
                'ids' => $request->product_ids,
            ]);

            return Product::query()
                ->with(['category', 'unit', 'stockBalances'])
                ->whereIn('id', $request->product_ids)
                ->orderBy('sort_order')
                ->orderBy('name_tr')
                ->get();
        }

        // Otherwise, use filters
        Log::info('Export - Using filters:', [
            'search' => $request->search,
            'category_id' => $request->category_id,
            'is_active' => $request->is_active,
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

    private function applyProductFilters(Builder $query, Request $request, ?string $movementDateFrom, ?string $movementDateTo): void
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
            ->when($movementDateFrom || $movementDateTo, function ($q) use ($movementDateFrom, $movementDateTo) {
                $q->whereHas('stockMovements', function ($mq) use ($movementDateFrom, $movementDateTo) {
                    $mq->when($movementDateFrom, fn ($inner) => $inner->whereDate('movement_date', '>=', $movementDateFrom))
                        ->when($movementDateTo, fn ($inner) => $inner->whereDate('movement_date', '<=', $movementDateTo));
                });
            });
    }

    private function applyMovementRangeRelationLoad(Builder $query, ?string $movementDateFrom, ?string $movementDateTo): void
    {
        if (!$movementDateFrom && !$movementDateTo) {
            return;
        }

        $query->with(['stockMovements' => function ($q) use ($movementDateFrom, $movementDateTo) {
            $q->select(['id', 'product_id', 'type', 'movement_date'])
                ->when($movementDateFrom, fn ($inner) => $inner->whereDate('movement_date', '>=', $movementDateFrom))
                ->when($movementDateTo, fn ($inner) => $inner->whereDate('movement_date', '<=', $movementDateTo))
                ->orderByDesc('movement_date');
        }]);
    }

    private function appendDerivedProductFields($products, ?string $movementDateFrom, ?string $movementDateTo): void
    {
        $products->transform(function ($product) use ($movementDateFrom, $movementDateTo) {
            $product->stock_quantity = $product->stockBalances->sum('quantity') ?? 0;

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

}
