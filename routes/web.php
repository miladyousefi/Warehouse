<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Restaurant\PublicOrderController;
use App\Http\Controllers\Warehouse\ActivityLogController;
use App\Http\Controllers\Warehouse\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Warehouse\Accounting\EntryController as AccountingEntryController;
use App\Http\Controllers\Warehouse\DashboardController;
use App\Http\Controllers\Warehouse\NotificationController;
use App\Http\Controllers\Warehouse\ProductCategoryController;
use App\Http\Controllers\Warehouse\ProductController;
use App\Http\Controllers\Warehouse\PurchaseOrderController;
use App\Http\Controllers\Warehouse\ReportController;
use App\Http\Controllers\Warehouse\Restaurant\OrderController as RestaurantOrderController;
use App\Http\Controllers\Warehouse\RestaurantMenu\RestaurantMenuController;
use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockMovementController;
use App\Http\Controllers\Warehouse\SupplierController;
use App\Http\Controllers\Warehouse\TaskController;
use App\Http\Controllers\Warehouse\UnitController;
use App\Http\Controllers\Warehouse\UserController;
use App\Http\Controllers\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('menu/{token}', [RestaurantMenuController::class, 'publicMenu'])->name('restaurant-menu.public');
Route::get('menu/table/{token}', [PublicOrderController::class, 'show'])->name('restaurant-order.public');
Route::post('menu/table/{token}/order', [PublicOrderController::class, 'storeOrder'])->name('restaurant-order.store');
Route::post('menu/table/{token}/call-waiter', [PublicOrderController::class, 'callWaiter'])->name('restaurant-order.call-waiter');

// Locale switch route: sets cookie and session, then redirects back
Route::get('locale/{locale}', function (string $locale) {
    $available = ['en', 'tr'];
    if (!in_array($locale, $available, true)) {
        $locale = config('app.locale');
    }

    // Store in session for authenticated users
    if (auth()->check()) {
        session(['locale' => $locale]);
    }

    // Also set cookie for persistent storage (1 year)
    $response = redirect()->back();
    return $response->withCookie(
        cookie('locale', $locale, 525600, '/', null, false, false)
    );
})->name('locale.switch');

// Settings Routes
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');
    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

// Warehouse Routes
Route::middleware(['auth', 'verified'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:activity_logs.view')->name('activity-logs.index');

    Route::resource('users', UserController::class)->parameters(['users' => 'user'])->except(['show']);
    Route::resource('roles', \App\Http\Controllers\Warehouse\RoleController::class)->except(['show']);

    Route::resource('products', ProductController::class)->parameters(['products' => 'product']);
    Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('products/{product}/stock', [ProductController::class, 'stock'])->name('products.stock');
    Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory'])->name('products.price-history');
    
    // Products export routes MUST come before resource routes
    Route::match(['get', 'post'], 'products/export/excel', [ProductController::class, 'exportExcel'])->middleware('permission:products.view')->name('products.export-excel');
    Route::match(['get', 'post'], 'products/export/pdf', [ProductController::class, 'exportPdf'])->middleware('permission:products.view')->name('products.export-pdf');
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->middleware('permission:products.delete')->name('products.bulk-delete');
    
    Route::resource('categories', ProductCategoryController::class)->parameters(['categories' => 'category']);
    Route::resource('units', UnitController::class)->parameters(['units' => 'unit']);
    Route::resource('suppliers', SupplierController::class)->parameters(['suppliers' => 'supplier']);
    Route::resource('warehouses', WarehouseController::class)->parameters(['warehouses' => 'warehouse']);

    Route::get('stock', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock.index');
    
    // Stock movements export routes MUST come before resource routes
    Route::get('stock-movements/export/excel', [StockMovementController::class, 'exportExcel'])->middleware('permission:stock_movements.view')->name('stock-movements.export-excel');
    Route::get('stock-movements/export/pdf', [StockMovementController::class, 'exportPdf'])->middleware('permission:stock_movements.view')->name('stock-movements.export-pdf');
    Route::get('stock-movements/products-by-warehouse', [StockMovementController::class, 'productsByWarehouse'])->name('stock-movements.products-by-warehouse');
    
    // Stock movements resource routes (must come after specific routes)
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->parameters(['stock-movements' => 'stock_movement']);

    Route::get('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::resource('purchase-orders', PurchaseOrderController::class)->parameters(['purchase_orders' => 'purchase_order']);

    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('reports/stock-valuation', [ReportController::class, 'stockValuation'])->middleware('permission:reports.view')->name('reports.stock-valuation');
    Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->middleware('permission:reports.view')->name('reports.low-stock');

    // Accounting Routes
    Route::get('accounting', [AccountingDashboardController::class, 'index'])->middleware('permission:accounting.view')->name('accounting.index');
    Route::get('accounting/create', [AccountingEntryController::class, 'create'])->middleware('permission:accounting.create')->name('accounting.create');
    Route::post('accounting', [AccountingEntryController::class, 'store'])->middleware('permission:accounting.create')->name('accounting.store');
    Route::get('accounting/{accounting_entry}/edit', [AccountingEntryController::class, 'edit'])->middleware('permission:accounting.edit')->name('accounting.edit');
    Route::patch('accounting/{accounting_entry}', [AccountingEntryController::class, 'update'])->middleware('permission:accounting.edit')->name('accounting.update');
    Route::delete('accounting/{accounting_entry}', [AccountingEntryController::class, 'destroy'])->middleware('permission:accounting.delete')->name('accounting.destroy');
    Route::get('accounting/report', [AccountingDashboardController::class, 'report'])->middleware('permission:accounting.view')->name('accounting.report');
    Route::get('accounting/export', [AccountingDashboardController::class, 'export'])->middleware('permission:accounting.view')->name('accounting.export');

    Route::get('restaurant-menu/show', [RestaurantMenuController::class, 'showMenu'])->middleware('permission:restaurant_menu.view')->name('restaurant-menu.show');
    Route::put('restaurant-menu/layout', [RestaurantMenuController::class, 'updateLayout'])->middleware('permission:restaurant_menu.edit')->name('restaurant-menu.layout');
    Route::post('restaurant-menu/categories', [RestaurantMenuController::class, 'storeCategory'])->middleware('permission:restaurant_menu.edit')->name('restaurant-menu.categories.store');
    Route::resource('restaurant-menu', RestaurantMenuController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['restaurant-menu' => 'restaurant_menu']);
    Route::get('restaurant-orders', [RestaurantOrderController::class, 'index'])
        ->middleware('permission:restaurant_orders.view')
        ->name('restaurant-orders.index');
    Route::get('restaurant-orders/manual/create', [RestaurantOrderController::class, 'createManual'])
        ->middleware('permission:restaurant_orders.take_order')
        ->name('restaurant-orders.manual.create');
    Route::post('restaurant-orders/manual', [RestaurantOrderController::class, 'storeManual'])
        ->middleware('permission:restaurant_orders.take_order')
        ->name('restaurant-orders.manual.store');
    Route::patch('restaurant-orders/{order}/status', [RestaurantOrderController::class, 'updateStatus'])
        ->middleware('permission:restaurant_orders.edit')
        ->name('restaurant-orders.update-status');
    Route::patch('restaurant-orders/calls/{call}/handled', [RestaurantOrderController::class, 'markCallHandled'])
        ->middleware('permission:restaurant_orders.edit')
        ->name('restaurant-orders.calls.handled');
    Route::post('restaurant-orders/tables', [RestaurantOrderController::class, 'storeTable'])
        ->middleware('permission:restaurant_orders.edit')
        ->name('restaurant-orders.tables.store');
    Route::patch('restaurant-orders/tables/{table}', [RestaurantOrderController::class, 'updateTable'])
        ->middleware('permission:restaurant_orders.edit')
        ->name('restaurant-orders.tables.update');
    Route::patch('restaurant-orders/tables/{table}/regenerate-link', [RestaurantOrderController::class, 'regenerateTableLink'])
        ->middleware('permission:restaurant_orders.edit')
        ->name('restaurant-orders.tables.regenerate-link');

    // Task Routes
    Route::resource('tasks', TaskController::class)->parameters(['tasks' => 'task']);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assignToUser'])->name('tasks.assign');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::patch('tasks/{task}/color', [TaskController::class, 'updateColor'])->name('tasks.update-color');

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::patch('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'delete'])->name('notifications.destroy');
});
