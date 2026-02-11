<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreProductRequest;
use App\Http\Requests\Warehouse\UpdateProductRequest;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\StockBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('products.view');

        $products = Product::query()
            ->with(['category', 'unit', 'stockBalances'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name_tr', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%")
                    ->orWhere('barcode', 'like', "%{$request->search}%");
            }))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15, ['*'], 'page', null)
            ->withQueryString()
            ->setPath(route('warehouse.products.index'));

        // Add stock_quantity to each product
        $products->getCollection()->transform(function ($product) {
            $product->stock_quantity = $product->stockBalances->sum('quantity') ?? 0;
            return $product;
        });

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
            'initial_stock' => $initialStock,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->toArray();
        $validated = $request->validated();
        $initialStock = $validated['initial_stock'] ?? null;
        unset($validated['initial_stock']);

        $product->update($validated);

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

}