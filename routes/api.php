<?php

use App\Http\Controllers\API\AIController;
use App\Http\Controllers\API\PushSubscriptionController;
use App\Http\Controllers\Warehouse\ActivityLogController;
use App\Http\Controllers\Warehouse\DashboardController;
use App\Http\Controllers\Warehouse\ProductCategoryController;
use App\Http\Controllers\Warehouse\ProductController;
use App\Http\Controllers\Warehouse\PurchaseOrderController;
use App\Http\Controllers\Warehouse\StockMovementController;
use App\Http\Controllers\Warehouse\SupplierController;
use App\Http\Controllers\Warehouse\UnitController;
use App\Http\Controllers\Warehouse\UserController;
use App\Http\Controllers\Warehouse\WarehouseController;
use App\Http\Controllers\Warehouse\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Warehouse\Accounting\EntryController as AccountingEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('warehouse')->name('api.warehouse.')->group(function () {
    // Dashboard API
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    
    // Products API
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', ProductCategoryController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    // Stock movements API
    Route::post('stock-movements', [StockMovementController::class, 'store']);
    
    // Purchase Orders API
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    
    // Accounting API
    Route::get('accounting/stats', [AccountingDashboardController::class, 'stats']);
    Route::apiResource('accounting', AccountingEntryController::class)
        ->parameters(['accounting' => 'accounting_entry'])
        ->only(['store', 'update', 'destroy']);
    Route::get('accounting/export', [AccountingDashboardController::class, 'export']);
    
    // Activity Logs API
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
});

// AI chat API routes use the authenticated web session from the SPA page.
Route::middleware(['web', 'auth', 'verified'])->prefix('ai')->group(function () {
    // Status and utilities
    Route::get('status', [AIController::class, 'status']);
    Route::get('models', [AIController::class, 'getModels']);

    // Simple chat
    Route::post('chat', [AIController::class, 'chat']);

    // Data analysis
    Route::post('analyze', [AIController::class, 'analyze']);

    // Query parsing
    Route::post('parse-query', [AIController::class, 'parseQuery']);

    // Conversations
    Route::post('conversation', [AIController::class, 'startConversation']);
    Route::get('conversations', [AIController::class, 'listConversations']);
    Route::get('conversation/{conversationId}', [AIController::class, 'getConversation']);
    Route::post('conversation/{conversationId}/message', [AIController::class, 'sendMessage']);
});

Route::middleware(['web', 'auth', 'verified'])->prefix('push')->group(function () {
    Route::get('config', [PushSubscriptionController::class, 'config']);
    Route::post('subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::post('test', [PushSubscriptionController::class, 'test']);
});
