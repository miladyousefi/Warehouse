<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('dashboard.view');

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->where('track_quantity', true)
            ->leftJoin('stock_balances', 'products.id', '=', 'stock_balances.product_id')
            ->select('products.id')
            ->groupBy('products.id', 'products.min_stock')
            ->havingRaw('COALESCE(SUM(stock_balances.quantity), 0) < COALESCE(products.min_stock, 0)')
            ->get()
            ->count();

        $totalProducts = Product::where('is_active', true)->count();
        $totalValue = StockBalance::query()
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->selectRaw('COALESCE(SUM(stock_balances.quantity * COALESCE(products.cost_price, 0)), 0) as total')
            ->value('total');

        $movementsTodayCount = StockMovement::query()
            ->whereDate('movement_date', now()->toDateString())
            ->count();

        // Get movements grouped by type for chart
        $movementsByType = StockMovement::query()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
        $totalMovementsCount = array_sum($movementsByType);

        $trendDays = 30;
        $trendStart = now()->subDays($trendDays - 1)->startOfDay();
        $trendEnd = now()->endOfDay();
        $trendRaw = StockMovement::query()
            ->whereBetween('movement_date', [$trendStart, $trendEnd])
            ->selectRaw('DATE(movement_date) as day, type, COUNT(*) as count')
            ->groupBy('day', 'type')
            ->orderBy('day')
            ->get();

        $trendByDayType = [];
        foreach ($trendRaw as $row) {
            $day = (string) $row->day;
            $type = (string) $row->type;
            $trendByDayType[$day][$type] = (int) $row->count;
        }

        $trendLabels = [];
        $trendByType = [
            StockMovement::TYPE_IN => [],
            StockMovement::TYPE_OUT => [],
            StockMovement::TYPE_TRANSFER => [],
            StockMovement::TYPE_ADJUSTMENT => [],
        ];
        $trendTotals = [];

        $cursor = $trendStart->copy();
        for ($i = 0; $i < $trendDays; $i++) {
            $day = $cursor->toDateString();
            $trendLabels[] = $day;

            $dayTotal = 0;
            foreach (array_keys($trendByType) as $type) {
                $count = (int) ($trendByDayType[$day][$type] ?? 0);
                $trendByType[$type][] = $count;
                $dayTotal += $count;
            }

            $trendTotals[] = $dayTotal;
            $cursor->addDay();
        }

        $warehouseMovementCountsRaw = StockMovement::query()
            ->whereBetween('movement_date', [$trendStart, $trendEnd])
            ->selectRaw('warehouse_id, COUNT(*) as count')
            ->groupBy('warehouse_id')
            ->orderByDesc('count')
            ->limit(8)
            ->get();
        $warehouseIds = $warehouseMovementCountsRaw->pluck('warehouse_id')->filter()->unique()->values();
        $warehouseMap = Warehouse::whereIn('id', $warehouseIds)->get()->keyBy('id');
        $movementsByWarehouse = $warehouseMovementCountsRaw
            ->map(function ($row) use ($warehouseMap) {
                $warehouse = $warehouseMap->get((int) $row->warehouse_id);
                return [
                    'warehouse_id' => (int) $row->warehouse_id,
                    'name_tr' => (string) ($warehouse?->name_tr ?? ('#' . (int) $row->warehouse_id)),
                    'name_en' => (string) ($warehouse?->name_en ?? ('#' . (int) $row->warehouse_id)),
                    'count' => (int) $row->count,
                ];
            })
            ->values();

        $stockValueByWarehouseRaw = StockBalance::query()
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->selectRaw('stock_balances.warehouse_id, COALESCE(SUM(stock_balances.quantity * COALESCE(products.cost_price, 0)), 0) as total')
            ->groupBy('stock_balances.warehouse_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $valueWarehouseIds = $stockValueByWarehouseRaw->pluck('warehouse_id')->filter()->unique()->values();
        $valueWarehouseMap = Warehouse::whereIn('id', $valueWarehouseIds)->get()->keyBy('id');
        $stockValueByWarehouse = $stockValueByWarehouseRaw
            ->map(function ($row) use ($valueWarehouseMap) {
                $warehouse = $valueWarehouseMap->get((int) $row->warehouse_id);
                return [
                    'warehouse_id' => (int) $row->warehouse_id,
                    'name_tr' => (string) ($warehouse?->name_tr ?? ('#' . (int) $row->warehouse_id)),
                    'name_en' => (string) ($warehouse?->name_en ?? ('#' . (int) $row->warehouse_id)),
                    'value' => (float) $row->total,
                ];
            })
            ->values();

        $activeSuppliersCount = Supplier::query()->where('is_active', true)->count();
        $activeWarehousesCount = Warehouse::query()->where('is_active', true)->count();
        $usersCount = User::query()->count();
        $openPurchaseOrdersCount = PurchaseOrder::query()
            ->whereIn('status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIAL,
            ])
            ->count();

        return Inertia::render('warehouse/Dashboard', [
            'lowStockCount' => $lowStockCount,
            'totalProducts' => $totalProducts,
            'totalValue' => (float) $totalValue,
            'totalMovementsCount' => (int) $totalMovementsCount,
            'movementsTodayCount' => (int) $movementsTodayCount,
            'movementTrend' => [
                'labels' => $trendLabels,
                'totals' => $trendTotals,
                'byType' => $trendByType,
            ],
            'activeSuppliersCount' => (int) $activeSuppliersCount,
            'activeWarehousesCount' => (int) $activeWarehousesCount,
            'usersCount' => (int) $usersCount,
            'openPurchaseOrdersCount' => (int) $openPurchaseOrdersCount,
            'movementsByType' => $movementsByType,
            'movementsByWarehouse' => $movementsByWarehouse,
            'stockValueByWarehouse' => $stockValueByWarehouse,
            'canRunGitPull' => app()->environment('local'),
        ]);
    }

    public function gitPull(Request $request): RedirectResponse
    {
        $this->authorize('dashboard.view');
        abort_unless(app()->environment('local'), 403);

        $steps = [
            ['label' => 'git pull', 'command' => ['git', 'pull', '--ff-only'], 'timeout' => 300],
            ['label' => 'composer update', 'command' => ['composer', 'update', '--no-interaction'], 'timeout' => 1800],
            ['label' => 'php artisan migrate', 'command' => ['php', 'artisan', 'migrate', '--force'], 'timeout' => 300],
        ];

        foreach ($steps as $step) {
            $result = Process::path(base_path())
                ->timeout($step['timeout'])
                ->run($step['command']);

            if (!$result->successful()) {
                return redirect()
                    ->route('warehouse.dashboard')
                    ->with('error', ucfirst($step['label']) . ' failed.');
            }
        }

        return redirect()
            ->route('warehouse.dashboard')
            ->with('success', 'Project updated successfully.');
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
