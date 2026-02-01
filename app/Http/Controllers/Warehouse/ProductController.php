<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreProductRequest;
use App\Http\Requests\Warehouse\UpdateProductRequest;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Models\ProductCategory;
use App\Models\Unit;
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
            ->with(['category', 'unit'])
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
            ->paginate(15)
            ->withQueryString();

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
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

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

        $product->load(['category', 'unit']);

        return Inertia::render('warehouse/products/Edit', [
            'product' => $product,
            'categories' => ProductCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'units' => Unit::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->toArray();
        $product->update($request->validated());

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
}
