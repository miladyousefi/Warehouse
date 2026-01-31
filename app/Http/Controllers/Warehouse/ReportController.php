<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('reports.view');

        return Inertia::render('warehouse/reports/Index');
    }

    public function stockValuation(Request $request): Response
    {
        $this->authorize('reports.view');

        $balances = StockBalance::with(['product.unit', 'product.category', 'warehouse'])
            ->get()
            ->map(function ($b) {
                $cost = (float) $b->quantity * (float) ($b->product->cost_price ?? 0);
                return [
                    'warehouse' => $b->warehouse->name_tr ?? $b->warehouse->name_en,
                    'product' => $b->product->name_tr ?? $b->product->name_en,
                    'quantity' => (float) $b->quantity,
                    'unit' => $b->product->unit->symbol ?? '-',
                    'unit_cost' => (float) ($b->product->cost_price ?? 0),
                    'total_value' => $cost,
                ];
            });

        $totalValue = $balances->sum('total_value');

        return Inertia::render('warehouse/reports/StockValuation', [
            'items' => $balances,
            'totalValue' => $totalValue,
        ]);
    }

    public function lowStock(Request $request): Response
    {
        $this->authorize('reports.view');

        $products = Product::where('track_quantity', true)
            ->where('is_active', true)
            ->with(['unit', 'category', 'stockBalances'])
            ->get()
            ->map(function ($product) {
                $totalStock = (float) $product->stockBalances->sum('quantity');
                return [
                    'product' => $product,
                    'total_stock' => $totalStock,
                    'min_stock' => (float) $product->min_stock,
                    'below_min' => $totalStock < (float) $product->min_stock,
                ];
            })
            ->filter(fn ($p) => $p['below_min'])
            ->values();

        return Inertia::render('warehouse/reports/LowStock', [
            'products' => $products,
        ]);
    }
}
