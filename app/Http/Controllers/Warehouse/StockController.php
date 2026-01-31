<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('stock.view');

        $warehouseId = $request->get('warehouse_id');
        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();

        $balances = StockBalance::query()
            ->with(['product.unit', 'product.category', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($request->search, fn ($q) => $q->whereHas('product', function ($q) use ($request) {
                $q->where('name_tr', 'like', "%{$request->search}%")
                    ->orWhere('name_en', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%");
            }))
            ->orderByRaw('(SELECT name_tr FROM products WHERE products.id = stock_balances.product_id)')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('warehouse/stock/Index', [
            'balances' => $balances,
            'warehouses' => $warehouses,
        ]);
    }
}
