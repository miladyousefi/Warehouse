<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\RestaurantTableCall;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('dashboard.view');

        $lowStockCount = Product::with('stockBalances')
            ->where('track_quantity', true)
            ->get()
            ->filter(fn ($product) => (float) $product->stockBalances->sum('quantity') < (float) $product->min_stock)
            ->count();

        $totalProducts = Product::where('is_active', true)->count();
        $totalValue = StockBalance::query()
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity * COALESCE(products.cost_price, 0)), 0) as total')
            ->value('total');

        $recentMovements = StockMovement::with(['product', 'warehouse', 'user'])
            ->latest('movement_date')
            ->limit(10)
            ->get();

        // Get movements grouped by type for chart
        $movementsByType = StockMovement::query()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $canViewRestaurantCalls =
            $request->user()?->can('restaurant_orders.view') ||
            $request->user()?->can('restaurant_orders.edit') ||
            $request->user()?->can('restaurant_orders.calls.handle');
        $canViewRestaurantTables = $request->user()?->can('restaurant_orders.view') || $request->user()?->can('restaurant_orders.edit');
        $canHandleCalls = $request->user()?->can('restaurant_orders.calls.handle') || $request->user()?->can('restaurant_orders.edit');

        $pendingCalls = [];
        $tables = [];

        if ($canViewRestaurantCalls) {
            $pendingCalls = RestaurantTableCall::query()
                ->with('table')
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        if ($canViewRestaurantTables) {
            $recentOrders = RestaurantOrder::query()
                ->with(['table', 'items.menuItem'])
                ->orderByDesc('id')
                ->limit(300)
                ->get();

            $ordersByTable = $recentOrders->groupBy('restaurant_table_id');

            $tables = RestaurantTable::query()
                ->where('is_active', true)
                ->orderBy('table_number')
                ->get()
                ->map(function (RestaurantTable $table) use ($ordersByTable) {
                    $tableOrders = $ordersByTable->get($table->id, collect());
                    $lastOrder = $tableOrders->first();
                    $orderLog = $tableOrders->take(10)->values();

                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'table_number' => $table->table_number,
                        'capacity' => $table->capacity,
                        'section' => $table->section,
                        'last_order' => $lastOrder,
                        'order_log' => $orderLog,
                    ];
                })
                ->values();
        }

        return Inertia::render('warehouse/Dashboard', [
            'lowStockCount' => $lowStockCount,
            'totalProducts' => $totalProducts,
            'totalValue' => (float) $totalValue,
            'recentMovements' => $recentMovements,
            'movementsByType' => $movementsByType,
            'restaurantBoard' => [
                'can_view_calls' => (bool) $canViewRestaurantCalls,
                'can_view_tables' => (bool) $canViewRestaurantTables,
                'can_handle_calls' => (bool) $canHandleCalls,
                'pending_calls' => $pendingCalls,
                'tables' => $tables,
            ],
        ]);
    }
}
