<?php

namespace App\Http\Controllers\Warehouse\Accounting;

use App\Http\Controllers\Warehouse\BaseController;
use App\Models\AccountingEntry;
use App\Models\RestaurantOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'month');
        $selectedYear = $request->query('year');
        $selectedMonth = $request->query('month');

        $entries = AccountingEntry::query()
            ->dateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->setPath('/warehouse/accounting');

        $income = AccountingEntry::byType('income')->dateRange($startDate, $endDate)->sum('amount');
        $expenses = AccountingEntry::byType('expense')->dateRange($startDate, $endDate)->sum('amount');
        $balance = $income - $expenses;

        $incomeByCategory = AccountingEntry::byType('income')
            ->dateRange($startDate, $endDate)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total')
            ->orderByDesc('total')
            ->get();

        $expenseByCategory = AccountingEntry::byType('expense')
            ->dateRange($startDate, $endDate)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total')
            ->orderByDesc('total')
            ->get();

        $stockValuation = StockBalance::query()
            ->join('products', 'stock_balances.product_id', '=', 'products.id')
            ->selectRaw('SUM(stock_balances.quantity * COALESCE(products.unit_price, 0)) as total')
            ->value('total') ?? 0;

        $walletInput = AccountingEntry::byType('income')
            ->where('category', 'wallet_input')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $walletOutput = AccountingEntry::byType('expense')
            ->where('category', 'wallet_output')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $walletBalance = $walletInput - $walletOutput;

        $dailyFlow = AccountingEntry::query()
            ->dateRange($startDate, $endDate)
            ->selectRaw('DATE(date) as day')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $priceHistoryStats = $this->priceHistoryStats($startDate, $endDate);
        $orderStats = $this->orderStats($startDate, $endDate);
        $orderDaily = $this->orderDaily($startDate, $endDate);
        $topOrderTables = $this->topOrderTables($startDate, $endDate);
        $warehouseOutValue = $this->warehouseOutValue($startDate, $endDate);
        $warehouseOutDaily = $this->warehouseOutDaily($startDate, $endDate);
        $lastMonthOrderStats = $this->lastMonthOrderStats();

        return Inertia::render('warehouse/accounting/Index', [
            'entries' => $entries,
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $balance,
            'stockValuation' => $stockValuation,
            'walletBalance' => $walletBalance,
            'walletInput' => $walletInput,
            'walletOutput' => $walletOutput,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'dailyFlow' => $dailyFlow,
            'priceHistoryStats' => $priceHistoryStats,
            'orderStats' => $orderStats,
            'orderDaily' => $orderDaily,
            'topOrderTables' => $topOrderTables,
            'warehouseOutValue' => $warehouseOutValue,
            'warehouseOutDaily' => $warehouseOutDaily,
            'lastMonthOrderStats' => $lastMonthOrderStats,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function report(Request $request)
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'year');

        $dailyData = AccountingEntry::query()
            ->dateRange($startDate, $endDate)
            ->groupBy('date', 'type')
            ->selectRaw('DATE(date) as date, type, SUM(amount) as total')
            ->orderBy('date')
            ->get();

        $monthlyIncome = AccountingEntry::byType('income')
            ->dateRange($startDate, $endDate)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total")
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->get();

        $monthlyExpense = AccountingEntry::byType('expense')
            ->dateRange($startDate, $endDate)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(amount) as total")
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->get();

        $categoryData = AccountingEntry::query()
            ->dateRange($startDate, $endDate)
            ->selectRaw('category as name, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('warehouse/accounting/Report', [
            'dailyData' => $dailyData,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'categoryData' => $categoryData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(Request $request): HttpResponse|BinaryFileResponse
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'month');
        $dataset = $request->query('dataset', 'entries');
        $format = $request->query('format', 'csv');

        if ($dataset === 'orders') {
            $rows = $this->exportOrderRows($startDate, $endDate);

            if ($format === 'xlsx') {
                $headings = ['Order Code', 'Date', 'Table', 'Status', 'Payment Status', 'Source', 'Subtotal', 'Estimated Cost', 'Gross Profit', 'Cancel Reason'];
                $data = array_map(fn (array $row) => array_values($row), $rows);

                return Excel::download(new class($data, $headings) implements FromArray, WithHeadings {
                    public function __construct(private array $rows, private array $headings)
                    {
                    }

                    public function array(): array
                    {
                        return $this->rows;
                    }

                    public function headings(): array
                    {
                        return $this->headings;
                    }
                }, "accounting-orders-{$startDate}-{$endDate}.xlsx");
            }

            $csv = "Order Code,Date,Table,Status,Payment Status,Source,Subtotal,Estimated Cost,Gross Profit,Cancel Reason\n";
            foreach ($rows as $row) {
                $csv .= sprintf(
                    "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $row['order_code'],
                    $row['date'],
                    str_replace('"', '""', (string) $row['table']),
                    $row['status'],
                    $row['payment_status'],
                    $row['source'],
                    $row['subtotal'],
                    $row['estimated_cost'],
                    $row['gross_profit'],
                    str_replace('"', '""', (string) $row['cancel_reason'])
                );
            }

            return response($csv)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=accounting-orders-{$startDate}-{$endDate}.csv");
        }

        $entries = AccountingEntry::query()
            ->dateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();

        $csv = "Date,Type,Category,Description,Amount\n";
        foreach ($entries as $entry) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $entry->date->format('Y-m-d'),
                $entry->type,
                $entry->category,
                str_replace('"', '""', (string) $entry->description),
                $entry->amount
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=accounting-{$startDate}-{$endDate}.csv");
    }

    public function stats(Request $request)
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'month');

        $income = AccountingEntry::byType('income')->dateRange($startDate, $endDate)->sum('amount');
        $expenses = AccountingEntry::byType('expense')->dateRange($startDate, $endDate)->sum('amount');

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income' => (float) $income,
            'expenses' => (float) $expenses,
            'balance' => (float) ($income - $expenses),
            'price_history' => $this->priceHistoryStats($startDate, $endDate),
        ]);
    }

    private function resolveDateRange(Request $request, string $default = 'month'): array
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $year = $request->query('year');
        $month = $request->query('month');

        if (!$startDate || !$endDate) {
            if ($year && $month) {
                $carbon = Carbon::createFromDate((int) $year, (int) $month, 1);
                $startDate = $startDate ?: $carbon->startOfMonth()->toDateString();
                $endDate = $endDate ?: $carbon->endOfMonth()->toDateString();

                return [$startDate, $endDate];
            }

            if ($year && !$month) {
                $carbon = Carbon::createFromDate((int) $year, 1, 1);
                $startDate = $startDate ?: $carbon->startOfYear()->toDateString();
                $endDate = $endDate ?: $carbon->endOfYear()->toDateString();

                return [$startDate, $endDate];
            }

            if ($default === 'year') {
                $startDate = $startDate ?: Carbon::now()->startOfYear()->toDateString();
                $endDate = $endDate ?: Carbon::now()->toDateString();
            } else {
                $startDate = $startDate ?: Carbon::now()->startOfMonth()->toDateString();
                $endDate = $endDate ?: Carbon::now()->endOfMonth()->toDateString();
            }
        }

        return [$startDate, $endDate];
    }

    private function priceHistoryStats(string $startDate, string $endDate): array
    {
        if (!Schema::hasTable('product_price_histories')) {
            return [
                'total_changes' => 0,
                'avg_change_pct' => 0,
                'increased_count' => 0,
                'decreased_count' => 0,
                'top_products' => [],
            ];
        }

        $hasNewPrice = Schema::hasColumn('product_price_histories', 'new_price');
        $newPriceCol = $hasNewPrice ? 'new_price' : 'price';

        $baseQuery = DB::table('product_price_histories as pph')
            ->whereBetween(DB::raw('DATE(pph.created_at)'), [$startDate, $endDate]);

        $totalChanges = (clone $baseQuery)->count();

        $avgChangePct = 0;
        $increasedCount = 0;
        $decreasedCount = 0;

        if ($hasNewPrice && Schema::hasColumn('product_price_histories', 'previous_price')) {
            $aggregates = (clone $baseQuery)
                ->selectRaw('AVG(CASE WHEN previous_price > 0 THEN ((new_price - previous_price) / previous_price) * 100 ELSE NULL END) as avg_pct')
                ->selectRaw('SUM(CASE WHEN new_price > previous_price THEN 1 ELSE 0 END) as increased_count')
                ->selectRaw('SUM(CASE WHEN new_price < previous_price THEN 1 ELSE 0 END) as decreased_count')
                ->first();

            $avgChangePct = (float) ($aggregates->avg_pct ?? 0);
            $increasedCount = (int) ($aggregates->increased_count ?? 0);
            $decreasedCount = (int) ($aggregates->decreased_count ?? 0);
        }

        $topProducts = (clone $baseQuery)
            ->join('products as p', 'p.id', '=', 'pph.product_id')
            ->selectRaw('pph.product_id, COALESCE(p.name_en, p.name_tr) as product_name, COUNT(*) as changes_count')
            ->when($hasNewPrice && Schema::hasColumn('product_price_histories', 'previous_price'), function ($query) {
                $query->selectRaw('AVG(ABS(pph.new_price - pph.previous_price)) as avg_delta');
            }, function ($query) use ($newPriceCol) {
                $query->selectRaw('AVG(ABS(pph.' . $newPriceCol . ')) as avg_delta');
            })
            ->groupBy('pph.product_id', 'product_name')
            ->orderByDesc('changes_count')
            ->limit(8)
            ->get();

        return [
            'total_changes' => (int) $totalChanges,
            'avg_change_pct' => round($avgChangePct, 2),
            'increased_count' => $increasedCount,
            'decreased_count' => $decreasedCount,
            'top_products' => $topProducts,
        ];
    }

    private function orderStats(string $startDate, string $endDate): array
    {
        $orderBase = RestaurantOrder::query()
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$startDate, $endDate]);

        $ordersCount = (clone $orderBase)->count();
        $paidOrders = (clone $orderBase)->where('payment_status', 'paid')->count();
        $grossSales = (float) (clone $orderBase)->where('status', '!=', 'cancelled')->sum('subtotal');

        $estimatedCost = $this->orderEstimatedCost($startDate, $endDate);

        return [
            'orders_count' => $ordersCount,
            'paid_orders' => $paidOrders,
            'gross_sales' => round($grossSales, 2),
            'estimated_cost' => round($estimatedCost, 2),
            'gross_profit' => round($grossSales - $estimatedCost, 2),
        ];
    }

    private function orderDaily(string $startDate, string $endDate)
    {
        return RestaurantOrder::query()
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$startDate, $endDate])
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as day')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw("SUM(CASE WHEN status != 'cancelled' THEN subtotal ELSE 0 END) as sales_total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    private function topOrderTables(string $startDate, string $endDate)
    {
        return RestaurantOrder::query()
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'restaurant_orders.restaurant_table_id')
            ->whereBetween(DB::raw('DATE(COALESCE(restaurant_orders.placed_at, restaurant_orders.created_at))'), [$startDate, $endDate])
            ->where('restaurant_orders.status', '!=', 'cancelled')
            ->selectRaw('restaurant_orders.restaurant_table_id as table_id')
            ->selectRaw('COALESCE(restaurant_tables.name, restaurant_tables.table_number, "Unknown") as table_name')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(restaurant_orders.subtotal) as sales_total')
            ->groupBy('table_id', 'table_name')
            ->orderByDesc('sales_total')
            ->limit(8)
            ->get();
    }

    private function orderEstimatedCost(string $startDate, string $endDate): float
    {
        return (float) DB::table('restaurant_order_items as roi')
            ->join('restaurant_orders as ro', 'ro.id', '=', 'roi.restaurant_order_id')
            ->join('restaurant_menu_item_ingredients as rmii', 'rmii.restaurant_menu_item_id', '=', 'roi.restaurant_menu_item_id')
            ->join('products as p', 'p.id', '=', 'rmii.product_id')
            ->whereBetween(DB::raw('DATE(COALESCE(ro.placed_at, ro.created_at))'), [$startDate, $endDate])
            ->where('ro.status', '!=', 'cancelled')
            ->sum(DB::raw('roi.quantity * rmii.quantity * COALESCE(p.cost_price, 0)'));
    }

    private function exportOrderRows(string $startDate, string $endDate): array
    {
        $orders = RestaurantOrder::query()
            ->with('table')
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$startDate, $endDate])
            ->orderByDesc(DB::raw('COALESCE(placed_at, created_at)'))
            ->get();

        $costMap = DB::table('restaurant_order_items as roi')
            ->join('restaurant_orders as ro', 'ro.id', '=', 'roi.restaurant_order_id')
            ->join('restaurant_menu_item_ingredients as rmii', 'rmii.restaurant_menu_item_id', '=', 'roi.restaurant_menu_item_id')
            ->join('products as p', 'p.id', '=', 'rmii.product_id')
            ->whereBetween(DB::raw('DATE(COALESCE(ro.placed_at, ro.created_at))'), [$startDate, $endDate])
            ->selectRaw('roi.restaurant_order_id as order_id')
            ->selectRaw('SUM(roi.quantity * rmii.quantity * COALESCE(p.cost_price, 0)) as estimated_cost')
            ->groupBy('order_id')
            ->pluck('estimated_cost', 'order_id');

        return $orders->map(function (RestaurantOrder $order) use ($costMap) {
            $cost = (float) ($costMap[$order->id] ?? 0);
            $subtotal = (float) $order->subtotal;

            return [
                'order_code' => $order->order_code,
                'date' => optional($order->placed_at ?? $order->created_at)?->toDateString(),
                'table' => $order->table?->name ?? $order->table?->table_number ?? 'Unknown',
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'source' => $order->source ?? '-',
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'estimated_cost' => number_format($cost, 2, '.', ''),
                'gross_profit' => number_format($subtotal - $cost, 2, '.', ''),
                'cancel_reason' => $order->cancel_reason ?? '',
            ];
        })->values()->all();
    }

    private function warehouseOutValue(string $startDate, string $endDate): float
    {
        return (float) StockMovement::query()
            ->where('type', 'out')
            ->whereBetween(DB::raw('DATE(movement_date)'), [$startDate, $endDate])
            ->sum(DB::raw('quantity * COALESCE(unit_cost, 0)'));
    }

    private function warehouseOutDaily(string $startDate, string $endDate)
    {
        return StockMovement::query()
            ->where('type', 'out')
            ->whereBetween(DB::raw('DATE(movement_date)'), [$startDate, $endDate])
            ->selectRaw('DATE(movement_date) as day')
            ->selectRaw('SUM(quantity * COALESCE(unit_cost, 0)) as out_total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    private function lastMonthOrderStats(): array
    {
        $start = Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $end = Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $base = RestaurantOrder::query()
            ->whereBetween(DB::raw('DATE(COALESCE(placed_at, created_at))'), [$start, $end]);

        return [
            'start_date' => $start,
            'end_date' => $end,
            'orders_count' => (clone $base)->count(),
            'sales_total' => (float) (clone $base)->where('status', '!=', 'cancelled')->sum('subtotal'),
        ];
    }
}
