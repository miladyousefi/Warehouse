<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\RestaurantTableCall;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $totalMovementsCount = array_sum($movementsByType);

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
            'totalMovementsCount' => (int) $totalMovementsCount,
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

    public function backupSql(Request $request): StreamedResponse
    {
        $this->authorize('dashboard.view');

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        abort_if($driver !== 'mysql', 422, 'Database backup export supports MySQL only.');

        $pdo = $connection->getPdo();
        $tablesResult = $connection->select('SHOW TABLES');
        $tables = array_map(
            fn ($row) => (string) array_values((array) $row)[0],
            $tablesResult
        );

        $filename = 'warehouse-backup-' . now()->format('Y-m-d-H-i-s') . '.sql';

        return response()->streamDownload(function () use ($tables, $connection, $pdo) {
            echo "-- Warehouse SQL Backup\n";
            echo '-- Generated At: ' . now()->toDateTimeString() . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $safeTable = str_replace('`', '``', $table);
                $createRow = (array) $connection->selectOne("SHOW CREATE TABLE `{$safeTable}`");
                $createSql = (string) ($createRow['Create Table'] ?? array_values($createRow)[1] ?? '');

                echo "-- --------------------------------------------------------\n";
                echo "-- Table: `{$table}`\n";
                echo "-- --------------------------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$safeTable}`;\n";
                echo $createSql . ";\n\n";

                foreach ($connection->table($table)->cursor() as $record) {
                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }

                        if (is_bool($value)) {
                            return $value ? '1' : '0';
                        }

                        if (is_int($value) || is_float($value)) {
                            return (string) $value;
                        }

                        return $pdo->quote((string) $value);
                    }, array_values((array) $record));

                    echo 'INSERT INTO `' . $safeTable . '` VALUES (' . implode(', ', $values) . ");\n";
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql; charset=utf-8',
        ]);
    }
}
