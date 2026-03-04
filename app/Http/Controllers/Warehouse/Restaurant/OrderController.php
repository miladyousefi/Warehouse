<?php

namespace App\Http\Controllers\Warehouse\Restaurant;

use App\Events\RestaurantOrderPlaced;
use App\Events\RestaurantOrderUpdated;
use App\Events\WaiterCalled;
use App\Http\Controllers\Warehouse\BaseController;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantTable;
use App\Models\RestaurantTableCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends BaseController
{
    public function index(Request $request): Response
    {
        $this->authorize('restaurant_orders.view');

        $orders = RestaurantOrder::query()
            ->with(['table', 'items.menuItem'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $calls = RestaurantTableCall::query()
            ->with('table')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('warehouse/restaurant-menu/Orders', [
            'orders' => $orders,
            'calls' => $calls,
            'stats' => [
                'pending_orders' => RestaurantOrder::query()->where('status', 'pending')->count(),
                'unpaid_orders' => RestaurantOrder::query()->where('payment_status', 'unpaid')->count(),
                'pending_calls' => $calls->count(),
            ],
            'tables' => RestaurantTable::query()
                ->where('is_active', true)
                ->orderBy('table_number')
                ->get()
                ->map(function (RestaurantTable $table) {
                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'table_number' => $table->table_number,
                        'capacity' => $table->capacity,
                        'section' => $table->section,
                        'is_active' => $table->is_active,
                        'qr_token' => $table->qr_token,
                        'order_url' => $table->qr_token ? route('restaurant-order.public', ['token' => $table->qr_token]) : null,
                    ];
                }),
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }

    public function updateStatus(Request $request, RestaurantOrder $order): RedirectResponse
    {
        $request->merge([
            'cancel_reason' => $request->input('cancel_reason') ?: null,
        ]);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,served,closed,cancelled',
            'payment_status' => 'required|in:unpaid,paid',
            'cancel_reason' => 'nullable|in:customer_request,out_of_stock,kitchen_issue,no_response,other',
        ]);

        $user = $request->user();
        $canEdit = $user?->can('restaurant_orders.edit');
        $canConfirmCancel = $user?->can('restaurant_orders.monitor.confirm_cancel');

        if (!$canEdit) {
            $isMonitorAllowedStatus = in_array($validated['status'], ['confirmed', 'cancelled'], true);
            $paymentStatusUnchanged = $validated['payment_status'] === $order->payment_status;

            if (!$canConfirmCancel || !$isMonitorAllowedStatus || !$paymentStatusUnchanged) {
                abort(403);
            }
        }

        if ($validated['status'] === 'cancelled' && empty($validated['cancel_reason'])) {
            throw ValidationException::withMessages([
                'cancel_reason' => 'Cancel reason is required.',
            ]);
        }

        if ($validated['status'] !== 'cancelled') {
            $validated['cancel_reason'] = null;
        }

        $previousStatus = $order->status;
        $order->update($validated);
        RestaurantOrderUpdated::dispatch($order->fresh('table'), $previousStatus);

        return back()->with('success', __('restaurantMenu.messages.orderUpdated'));
    }

    public function markCallHandled(RestaurantTableCall $call): RedirectResponse
    {
        if (!request()->user()?->can('restaurant_orders.edit') && !request()->user()?->can('restaurant_orders.calls.handle')) {
            abort(403);
        }

        $call->update([
            'status' => 'handled',
            'handled_at' => now(),
        ]);
        WaiterCalled::dispatch($call->fresh('table'));

        return back()->with('success', __('restaurantMenu.messages.callHandled'));
    }

    public function createManual(): Response
    {
        $this->authorize('restaurant_orders.take_order');

        return Inertia::render('warehouse/restaurant-menu/ManualOrderCreate', [
            'tables' => RestaurantTable::query()
                ->where('is_active', true)
                ->orderBy('table_number')
                ->get(['id', 'name', 'table_number', 'capacity', 'section']),
            'categories' => RestaurantMenuCategory::query()
                ->where('is_active', true)
                ->with(['items' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function kitchen(): Response
    {
        $this->authorize('restaurant_orders.view');

        $orders = RestaurantOrder::query()
            ->with(['table', 'items.menuItem'])
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        $confirmedOrders = RestaurantOrder::query()
            ->with(['table', 'items.menuItem'])
            ->where('status', 'confirmed')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return Inertia::render('warehouse/restaurant-menu/KitchenMonitor', [
            'orders' => $orders,
            'confirmedOrders' => $confirmedOrders,
        ]);
    }

    public function show(RestaurantOrder $order): Response
    {
        $this->authorize('restaurant_orders.view');

        $order->load(['table', 'items.menuItem']);

        return Inertia::render('warehouse/restaurant-menu/OrderShow', [
            'order' => $order,
        ]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $this->authorize('restaurant_orders.take_order');

        $validated = $request->validate([
            'restaurant_table_id' => 'required|exists:restaurant_tables,id',
            'customer_note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:restaurant_menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated): void {
            $order = $this->createOrderFromValidated(
                $validated,
                (int) $validated['restaurant_table_id'],
                'manual'
            );
            RestaurantOrderPlaced::dispatch($order);
        });

        return redirect()
            ->route('warehouse.restaurant-orders.manual.create')
            ->with('success', __('restaurantMenu.messages.manualOrderCreated'));
    }

    public function storeTable(Request $request): RedirectResponse
    {
        $this->authorize('restaurant_orders.edit');

        $validated = $request->validate([
            'table_number' => 'required|string|max:50|unique:restaurant_tables,table_number',
            'name' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'section' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        RestaurantTable::create(array_merge($validated, [
            'qr_token' => $this->generateQrToken(),
        ]));

        return back()->with('success', __('restaurantMenu.messages.tableCreated'));
    }

    public function updateTable(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->authorize('restaurant_orders.edit');

        $validated = $request->validate([
            'table_number' => 'required|string|max:50|unique:restaurant_tables,table_number,' . $table->id,
            'name' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'section' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
        ]);

        if (!$table->qr_token) {
            $table->qr_token = $this->generateQrToken();
        }

        $table->fill($validated);
        $table->save();

        return back()->with('success', __('restaurantMenu.messages.tableUpdated'));
    }

    public function regenerateTableLink(RestaurantTable $table): RedirectResponse
    {
        $this->authorize('restaurant_orders.edit');

        $table->update([
            'qr_token' => $this->generateQrToken(),
        ]);

        return back()->with('success', __('restaurantMenu.messages.tableLinkRegenerated'));
    }

    public function destroyTable(RestaurantTable $table): RedirectResponse
    {
        $this->authorize('restaurant_orders.edit');

        $table->delete();

        return back()->with('success', __('restaurantMenu.messages.tableDeleted'));
    }

    /**
     * @param  array{
     *   customer_note?: string|null,
     *   items: array<int, array{id:int|string, quantity:int|string, note?:string|null}>
     * }  $validated
     */
    private function createOrderFromValidated(array $validated, int $tableId, string $source = 'manual'): RestaurantOrder
    {
        $requestedItems = collect($validated['items']);
        $menuItems = RestaurantMenuItem::query()
            ->whereIn('id', $requestedItems->pluck('id')->unique()->values())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($menuItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('restaurantMenu.messages.invalidOrderItems'),
            ]);
        }

        $order = RestaurantOrder::create([
            'restaurant_table_id' => $tableId,
            'order_code' => $this->generateOrderCode(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'customer_note' => $validated['customer_note'] ?? null,
            'source' => $source,
            'placed_at' => now(),
        ]);

        $subtotal = 0;
        $lineCount = 0;

        foreach ($requestedItems as $item) {
            $menuItem = $menuItems->get((int) $item['id']);
            if (!$menuItem) {
                continue;
            }

            $qty = (int) $item['quantity'];
            $unitPrice = (float) $menuItem->sale_price;
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;
            $lineCount++;

            RestaurantOrderItem::create([
                'restaurant_order_id' => $order->id,
                'restaurant_menu_item_id' => $menuItem->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
                'note' => $item['note'] ?? null,
            ]);
        }

        if ($lineCount === 0) {
            throw ValidationException::withMessages([
                'items' => __('restaurantMenu.messages.invalidOrderItems'),
            ]);
        }

        $order->update(['subtotal' => $subtotal]);

        return $order;
    }

    private function generateOrderCode(): string
    {
        return 'RO-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (RestaurantTable::query()->where('qr_token', $token)->exists());

        return $token;
    }
}
