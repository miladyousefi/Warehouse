<?php

use App\Http\Controllers\Warehouse\DashboardController;
use App\Http\Controllers\Warehouse\ProductCategoryController;
use App\Http\Controllers\Warehouse\ProductController;
use App\Http\Controllers\Warehouse\PurchaseOrderController;
use App\Http\Controllers\Warehouse\ReportController;
use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockMovementController;
use App\Http\Controllers\Warehouse\SupplierController;
use App\Http\Controllers\Warehouse\UnitController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

    Route::resource('products', ProductController::class)->parameters(['products' => 'product']);
    Route::resource('categories', ProductCategoryController::class)->parameters(['categories' => 'category']);
    Route::resource('units', UnitController::class)->parameters(['units' => 'unit']);
    Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'supplier']);
    Route::resource('warehouses', WarehouseController::class)->parameters(['warehouses' => 'warehouse']);

    Route::get('stock', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock.index');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store'])->parameters(['stock-movements' => 'stock_movement']);

    Route::get('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase_orders' => 'purchase_order']);

    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('reports/stock-valuation', [ReportController::class, 'stockValuation'])->middleware('permission:reports.view')->name('reports.stock-valuation');
    Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->middleware('permission:reports.view')->name('reports.low-stock');
});
