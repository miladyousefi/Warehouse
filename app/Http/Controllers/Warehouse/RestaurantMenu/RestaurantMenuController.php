<?php

namespace App\Http\Controllers\Warehouse\RestaurantMenu;

use App\Http\Controllers\Warehouse\BaseController;
use App\Models\Product;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantMenuItemIngredient;
use App\Models\RestaurantMenuSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantMenuController extends BaseController
{
    public function index(Request $request): Response
    {
        $this->authorize('restaurant_menu.view');

        $items = RestaurantMenuItem::query()
            ->with(['category', 'ingredients.product.unit'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name_tr', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->paginate(15)
            ->withQueryString()
            ->setPath('/warehouse/restaurant-menu');

        $items->getCollection()->transform(function (RestaurantMenuItem $item) {
            $foodCost = $item->ingredients->sum(function (RestaurantMenuItemIngredient $ingredient) {
                return ((float) $ingredient->quantity) * ((float) ($ingredient->product?->unit_price ?? 0));
            });

            $item->food_cost = round($foodCost, 2);
            $item->profit = round(((float) $item->sale_price) - $item->food_cost, 2);
            $item->food_cost_ratio = $item->sale_price > 0
                ? round(($item->food_cost / (float) $item->sale_price) * 100, 2)
                : 0;

            return $item;
        });

        $setting = $this->currentSetting();

        return Inertia::render('warehouse/restaurant-menu/Index', [
            'items' => $items,
            'categories' => RestaurantMenuCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'setting' => $setting,
            'shareUrl' => ($setting->share_token ?? null)
                ? route('restaurant-menu.public', ['token' => $setting->share_token])
                : null,
            'templates' => $this->templates(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('restaurant_menu.create');

        return Inertia::render('warehouse/restaurant-menu/Create', [
            'categories' => RestaurantMenuCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::query()->where('is_active', true)->with('unit')->orderBy('name_tr')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('restaurant_menu.create');

        $validated = $this->validateItem($request);

        DB::transaction(function () use ($validated, $request) {
            $ingredients = $validated['ingredients'] ?? [];
            unset($validated['ingredients']);
            unset($validated['image'], $validated['images'], $validated['cover_image_key'], $validated['remove_image']);

            $item = RestaurantMenuItem::create($validated);
            $this->syncItemImages($request, $item);

            foreach ($ingredients as $ingredient) {
                RestaurantMenuItemIngredient::create([
                    'restaurant_menu_item_id' => $item->id,
                    'product_id' => $ingredient['product_id'],
                    'quantity' => $ingredient['quantity'],
                ]);
            }
        });

        return redirect()->route('warehouse.restaurant-menu.index')->with('success', __('restaurantMenu.messages.created'));
    }

    public function edit(RestaurantMenuItem $restaurant_menu): Response
    {
        $this->authorize('restaurant_menu.edit');

        $restaurant_menu->load(['ingredients.product.unit']);

        return Inertia::render('warehouse/restaurant-menu/Edit', [
            'item' => $restaurant_menu,
            'categories' => RestaurantMenuCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::query()->where('is_active', true)->with('unit')->orderBy('name_tr')->get(),
        ]);
    }

    public function update(Request $request, RestaurantMenuItem $restaurant_menu): RedirectResponse
    {
        $this->authorize('restaurant_menu.edit');

        $validated = $this->validateItem($request, $restaurant_menu);

        DB::transaction(function () use ($validated, $restaurant_menu, $request) {
            $ingredients = $validated['ingredients'] ?? [];
            unset($validated['ingredients']);
            unset($validated['image'], $validated['images'], $validated['cover_image_key'], $validated['remove_image']);

            $restaurant_menu->update($validated);
            $this->syncItemImages($request, $restaurant_menu);
            $restaurant_menu->ingredients()->delete();

            foreach ($ingredients as $ingredient) {
                RestaurantMenuItemIngredient::create([
                    'restaurant_menu_item_id' => $restaurant_menu->id,
                    'product_id' => $ingredient['product_id'],
                    'quantity' => $ingredient['quantity'],
                ]);
            }
        });

        return redirect()->route('warehouse.restaurant-menu.index')->with('success', __('restaurantMenu.messages.updated'));
    }

    public function destroy(RestaurantMenuItem $restaurant_menu): RedirectResponse
    {
        $this->authorize('restaurant_menu.delete');

        $images = collect($restaurant_menu->image_gallery_paths ?? [])
            ->push($restaurant_menu->image_path)
            ->filter()
            ->unique()
            ->values()
            ->all();
        Storage::disk('public')->delete($images);

        $restaurant_menu->delete();

        return redirect()->route('warehouse.restaurant-menu.index')->with('success', __('restaurantMenu.messages.deleted'));
    }

    public function updateLayout(Request $request): RedirectResponse
    {
        $this->authorize('restaurant_menu.edit');

        $validated = $request->validate([
            'layout_type' => 'required|string|in:template_1,template_2,template_3,template_4,template_5',
            'is_public' => 'boolean',
        ]);

        $setting = $this->currentSetting();

        $setting->update(array_merge($validated, [
            'is_public' => (bool) ($validated['is_public'] ?? true),
        ]));

        return back()->with('success', __('restaurantMenu.messages.layoutUpdated'));
    }

    public function showMenu(): Response
    {
        $this->authorize('restaurant_menu.view');

        $setting = $this->currentSetting();

        $categories = RestaurantMenuCategory::query()
            ->where('is_active', true)
            ->with(['items' => function ($q) {
                $q->where('is_active', true)->with(['ingredients.product.unit'])->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('warehouse/restaurant-menu/ShowMenu', [
            'setting' => $setting,
            'templates' => $this->templates(),
            'categories' => $categories,
        ]);
    }

    public function publicMenu(string $token): Response
    {
        $setting = RestaurantMenuSetting::query()
            ->where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        $categories = RestaurantMenuCategory::query()
            ->where('is_active', true)
            ->with(['items' => function ($q) {
                $q->where('is_active', true)->with(['ingredients.product.unit'])->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('warehouse/restaurant-menu/PublicMenu', [
            'setting' => $setting,
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorize('restaurant_menu.edit');

        $validated = $request->validate([
            'name_tr' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'icon' => 'nullable|string|max:30',
            'image' => 'nullable|image|max:5120',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $categoryData = [
            'name_tr' => $validated['name_tr'],
            'name_en' => $validated['name_en'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        if ($request->hasFile('image') && Schema::hasColumn('restaurant_menu_categories', 'image_path')) {
            $categoryData['image_path'] = $request->file('image')->store('restaurant-menu/categories', 'public');
        }

        if (!Schema::hasColumn('restaurant_menu_categories', 'icon')) {
            unset($categoryData['icon']);
        }

        RestaurantMenuCategory::create($categoryData);

        return back()->with('success', __('restaurantMenu.messages.categoryCreated'));
    }

    private function validateItem(Request $request, ?RestaurantMenuItem $item = null): array
    {
        $validated = $request->validate([
            'restaurant_menu_category_id' => 'nullable|exists:restaurant_menu_categories,id',
            'name_tr' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_tr' => 'nullable|string',
            'description_en' => 'nullable|string',
            'image' => 'nullable|image|max:5120', // backward compatibility
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:5120',
            'cover_image_key' => 'nullable|string|max:30',
            'remove_image' => 'nullable|boolean',
            'sale_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.product_id' => 'required|distinct|exists:products,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        return $validated;
    }

    private function syncItemImages(Request $request, RestaurantMenuItem $item): void
    {
        $hasImagePath = Schema::hasColumn('restaurant_menu_items', 'image_path');
        $hasGalleryPaths = Schema::hasColumn('restaurant_menu_items', 'image_gallery_paths');

        if (!$hasImagePath && !$hasGalleryPaths) {
            return;
        }

        $existingPaths = collect($item->image_gallery_paths ?? [])
            ->filter()
            ->values()
            ->all();

        if (empty($existingPaths) && $item->image_path) {
            $existingPaths = [$item->image_path];
        }

        if ($request->boolean('remove_image') && $item->image_path) {
            Storage::disk('public')->delete($item->image_path);
            $existingPaths = array_values(array_filter($existingPaths, fn ($path) => $path !== $item->image_path));
        }

        $uploadedPaths = [];
        if ($request->hasFile('image')) {
            $uploadedPaths[] = $request->file('image')->store('restaurant-menu/items', 'public');
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $uploadedPaths[] = $imageFile->store('restaurant-menu/items', 'public');
            }
        }

        $allPaths = array_values(array_unique(array_merge($existingPaths, $uploadedPaths)));
        $coverPath = $item->image_path ?: ($allPaths[0] ?? null);
        $coverImageKey = (string) $request->input('cover_image_key', '');

        if ($coverImageKey !== '') {
            if (str_starts_with($coverImageKey, 'old:')) {
                $idx = (int) substr($coverImageKey, 4);
                if (isset($existingPaths[$idx])) {
                    $coverPath = $existingPaths[$idx];
                }
            } elseif (str_starts_with($coverImageKey, 'new:')) {
                $idx = (int) substr($coverImageKey, 4);
                if (isset($uploadedPaths[$idx])) {
                    $coverPath = $uploadedPaths[$idx];
                }
            }
        }

        if ($coverPath && !in_array($coverPath, $allPaths, true)) {
            $coverPath = $allPaths[0] ?? null;
        }

        $payload = [];
        if ($hasImagePath) {
            $payload['image_path'] = $coverPath;
        }
        if ($hasGalleryPaths) {
            $payload['image_gallery_paths'] = $allPaths;
        }

        if (!empty($payload)) {
            $item->update($payload);
        }
    }

    private function templates(): array
    {
        return [
            ['id' => 'template_1', 'label' => __('restaurantMenu.templates.template_1')],
            ['id' => 'template_2', 'label' => __('restaurantMenu.templates.template_2')],
            ['id' => 'template_3', 'label' => __('restaurantMenu.templates.template_3')],
            ['id' => 'template_4', 'label' => __('restaurantMenu.templates.template_4')],
            ['id' => 'template_5', 'label' => __('restaurantMenu.templates.template_5')],
        ];
    }

    private function currentSetting(): RestaurantMenuSetting
    {
        $hasShareToken = Schema::hasTable('restaurant_menu_settings')
            && Schema::hasColumn('restaurant_menu_settings', 'share_token');

        $createPayload = [
            'layout_type' => 'template_1',
            'is_public' => true,
        ];

        if ($hasShareToken) {
            $createPayload['share_token'] = Str::random(32);
        }

        $setting = RestaurantMenuSetting::query()->firstOrCreate([], $createPayload);

        if ($hasShareToken && !$setting->share_token) {
            $setting->share_token = Str::random(32);
            $setting->save();
        }

        return $setting;
    }
}
