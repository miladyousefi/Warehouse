<?php

namespace App\Http\Controllers\Warehouse\Accounting;

use App\Http\Controllers\Warehouse\BaseController;
use App\Models\AccountingEntry;
use App\Models\StockBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'month');

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

    public function export(Request $request)
    {
        $this->authorize('accounting.view');

        [$startDate, $endDate] = $this->resolveDateRange($request, 'month');

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

        if (!$startDate || !$endDate) {
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
}
