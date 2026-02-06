<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreProductCategoryRequest;
use App\Http\Requests\Warehouse\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('categories.view');

        $categories = ProductCategory::with('parent')
            ->withCount('products')
            ->when($request->search, fn ($q) => $q->where('name_tr', 'like', "%{$request->search}%")
                ->orWhere('name_en', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15)
            ->withQueryString()
            ->setPath('/warehouse/categories');

        return Inertia::render('warehouse/categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('categories.create');

        return Inertia::render('warehouse/categories/Create', [
            'parents' => ProductCategory::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name_en']);
        $category = ProductCategory::create($validated);

        ActivityLogger::log('category.create', __('Category created'), $category, null, $category->toArray());

        return redirect()->route('warehouse.categories.index')->with('success', __('Category created.'));
    }

    public function edit(ProductCategory $category): Response
    {
        $this->authorize('categories.edit');

        return Inertia::render('warehouse/categories/Edit', [
            'category' => $category,
            'parents' => ProductCategory::whereNull('parent_id')->where('is_active', true)->where('id', '!=', $category->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $old = $category->toArray();
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name_en']);
        $category->update($validated);

        ActivityLogger::log('category.update', __('Category updated'), $category, $old, $category->fresh()->toArray());

        return redirect()->route('warehouse.categories.index')->with('success', __('Category updated.'));
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $this->authorize('categories.delete');

        if ($category->products()->exists()) {
            return back()->with('error', __('Category has products and cannot be deleted.'));
        }

        $data = $category->toArray();
        $category->delete();

        ActivityLogger::log('category.delete', __('Category deleted'), null, $data, null);

        return redirect()->route('warehouse.categories.index')->with('success', __('Category deleted.'));
    }
}
