<?php

use App\Http\Controllers\Warehouse\ActivityLogController;
use App\Http\Controllers\Warehouse\DashboardController;
use App\Http\Controllers\Warehouse\ProductCategoryController;
use App\Http\Controllers\Warehouse\ProductController;
use App\Http\Controllers\Warehouse\PurchaseOrderController;
use App\Http\Controllers\Warehouse\ReportController;
use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockMovementController;
use App\Http\Controllers\Warehouse\SupplierController;
use App\Http\Controllers\Warehouse\UnitController;
use App\Http\Controllers\Warehouse\UserController;
use App\Http\Controllers\Warehouse\WarehouseController;
use App\Http\Controllers\Warehouse\AccountingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api/warehouse')->name('api.warehouse.')->group(function () {
    // Dashboard API
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    
    // Products API
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', ProductCategoryController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    // Stock API
    Route::get('stock/summary', [StockController::class, 'summary']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);
    
    // Purchase Orders API
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    
    // Accounting API
    Route::get('accounting/stats', [AccountingController::class, 'stats']);
    Route::apiResource('accounting', AccountingController::class);
    Route::get('accounting/export', [AccountingController::class, 'export']);
    
    // Activity Logs API
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
});
