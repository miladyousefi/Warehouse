<?php

namespace App\Http\Controllers\Warehouse;

use App\Models\AccountingEntry;
use App\Models\StockBalance;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorize('accounting.view');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $entries = AccountingEntry::dateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->setPath('/warehouse/accounting');

        $income = AccountingEntry::byType('income')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $expenses = AccountingEntry::byType('expense')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $balance = $income - $expenses;

        $incomeByCategory = AccountingEntry::byType('income')
            ->dateRange($startDate, $endDate)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total')
            ->get();

        $expenseByCategory = AccountingEntry::byType('expense')
            ->dateRange($startDate, $endDate)
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total')
            ->get();

        // Calculate stock valuation (all current warehouse stock at current prices)
        $stockValuation = StockBalance::query()
            ->join('products', 'stock_balances.product_id', '=', 'products.id')
            ->selectRaw('SUM(stock_balances.quantity * COALESCE(products.unit_price, 0)) as total')
            ->value('total') ?? 0;

        // Calculate wallet balance (all wallet transactions, defaults to income - expense for wallet category)
        $walletInput = AccountingEntry::byType('income')
            ->where('category', 'wallet_input')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $walletOutput = AccountingEntry::byType('expense')
            ->where('category', 'wallet_output')
            ->dateRange($startDate, $endDate)
            ->sum('amount');

        $walletBalance = $walletInput - $walletOutput;

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
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function create()
    {
        $this->authorize('accounting.create');

        return Inertia::render('warehouse/accounting/Create', [
            'categories' => [
                'income' => [
                    'sales' => 'accounting.categories.sales',
                    'service' => 'accounting.categories.service',
                    'wallet_input' => 'accounting.categories.walletInput',
                    'other' => 'accounting.categories.other',
                ],
                'expense' => [
                    'materials' => 'accounting.categories.materials',
                    'salaries' => 'accounting.categories.salaries',
                    'utilities' => 'accounting.categories.utilities',
                    'transport' => 'accounting.categories.transport',
                    'wallet_output' => 'accounting.categories.walletOutput',
                    'other' => 'accounting.categories.other',
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('accounting.create');

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        AccountingEntry::create($validated);

        return redirect()->route('warehouse.accounting.index')
            ->with('success', 'Accounting entry created successfully');
    }

    public function edit(AccountingEntry $accountingEntry)
    {
        $this->authorize('accounting.edit');

        return Inertia::render('warehouse/accounting/Edit', [
            'entry' => $accountingEntry,
            'categories' => [
                'income' => [
                    'sales' => 'accounting.categories.sales',
                    'service' => 'accounting.categories.service',
                    'wallet_input' => 'accounting.categories.walletInput',
                    'other' => 'accounting.categories.other',
                ],
                'expense' => [
                    'materials' => 'accounting.categories.materials',
                    'salaries' => 'accounting.categories.salaries',
                    'utilities' => 'accounting.categories.utilities',
                    'transport' => 'accounting.categories.transport',
                    'wallet_output' => 'accounting.categories.walletOutput',
                    'other' => 'accounting.categories.other',
                ],
            ],
        ]);
    }

    public function update(Request $request, AccountingEntry $accountingEntry)
    {
        $this->authorize('accounting.edit');

        $validated = $request->validate([
            'date' => 'required|date',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $accountingEntry->update($validated);

        return redirect()->route('warehouse.accounting.index')
            ->with('success', 'Accounting entry updated successfully');
    }

    public function destroy(AccountingEntry $accountingEntry)
    {
        $this->authorize('accounting.delete');

        $accountingEntry->delete();

        return redirect()->route('warehouse.accounting.index')
            ->with('success', 'Accounting entry deleted successfully');
    }

    public function report(Request $request)
    {
        $this->authorize('accounting.view');

        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::now()->format('Y-m-d'));

        $dailyData = AccountingEntry::dateRange($startDate, $endDate)
            ->groupBy('date', 'type')
            ->selectRaw('date, type, SUM(amount) as total')
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

        return Inertia::render('warehouse/accounting/Report', [
            'dailyData' => $dailyData,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('accounting.view');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $entries = AccountingEntry::dateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();

        $csv = "Date,Type,Category,Description,Amount\n";
        foreach ($entries as $entry) {
            $csv .= "\"{$entry->date->format('Y-m-d')}\",\"{$entry->type}\",\"{$entry->category}\",\"{$entry->description}\",\"{$entry->amount}\"\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=accounting-{$startDate}-{$endDate}.csv");
    }
}
