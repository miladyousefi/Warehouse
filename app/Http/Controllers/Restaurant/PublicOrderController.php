<?php

namespace App\Http\Controllers\Restaurant;

use App\Events\RestaurantOrderPlaced;
use App\Events\WaiterCalled;
use App\Http\Controllers\Controller;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuSetting;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantTable;
use App\Models\RestaurantTableCall;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicOrderController extends Controller
{
    public function show(string $token): Response
    {
        $table = RestaurantTable::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $setting = RestaurantMenuSetting::query()->first();

        $categories = RestaurantMenuCategory::query()
            ->where('is_active', true)
            ->with(['items' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('warehouse/restaurant-menu/PublicOrderMenu', [
            'setting' => $setting,
            'table' => $table,
            'categories' => $categories,
        ]);
    }

    public function storeOrder(Request $request, string $token): RedirectResponse
    {
        $table = RestaurantTable::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:restaurant_menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $table): void {
            $requestedItems = collect($validated['items']);
            $menuItems = \App\Models\RestaurantMenuItem::query()
                ->whereIn('id', $requestedItems->pluck('id')->unique()->values())
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            $order = RestaurantOrder::create([
                'restaurant_table_id' => $table->id,
                'order_code' => $this->generateOrderCode(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'customer_note' => $validated['customer_note'] ?? null,
                'source' => 'qr',
                'placed_at' => now(),
            ]);

            $subtotal = 0;

            foreach ($requestedItems as $item) {
                $menuItem = $menuItems->get((int) $item['id']);
                if (!$menuItem) {
                    continue;
                }

                $qty = (int) $item['quantity'];
                $unitPrice = (float) $menuItem->sale_price;
                $lineTotal = $qty * $unitPrice;
                $subtotal += $lineTotal;

                RestaurantOrderItem::create([
                    'restaurant_order_id' => $order->id,
                    'restaurant_menu_item_id' => $menuItem->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'note' => $item['note'] ?? null,
                ]);
            }

            $order->update(['subtotal' => $subtotal]);

            RestaurantOrderPlaced::dispatch($order);
        });

        return back()->with('success', __('restaurantMenu.messages.orderPlaced'));
    }

    public function callWaiter(Request $request, string $token): RedirectResponse
    {
        $table = RestaurantTable::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $alreadyPending = RestaurantTableCall::query()
            ->where('restaurant_table_id', $table->id)
            ->where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subMinutes(2))
            ->exists();

        if ($alreadyPending) {
            return back()->with('success', __('restaurantMenu.messages.waiterAlreadyCalled'));
        }

        $call = RestaurantTableCall::create([
            'restaurant_table_id' => $table->id,
            'status' => 'pending',
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
        ]);

        WaiterCalled::dispatch($call);

        return back()->with('success', __('restaurantMenu.messages.waiterCalled'));
    }

    private function generateOrderCode(): string
    {
        return 'RO-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }
}
