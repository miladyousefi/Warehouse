<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        return Inertia::render('warehouse/Dashboard', [
            'lowStockCount' => $lowStockCount,
            'totalProducts' => $totalProducts,
            'totalValue' => (float) $totalValue,
            'recentMovements' => $recentMovements,
        ]);
    }
}
