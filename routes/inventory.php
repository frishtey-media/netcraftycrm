<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Inventory\InventoryAuthController;
use App\Http\Controllers\Inventory\DashboardController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\SaleController;

use App\Http\Controllers\Inventory\WarehouseController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\CallnumberIssueController;

Route::prefix('inventory')->group(function () {

    // Login Routes
    Route::get('/login', [InventoryAuthController::class, 'showLogin'])
        ->name('inventory.login');

    Route::post('/login', [InventoryAuthController::class, 'login'])
        ->name('inventory.login.submit');

    // Protected Routes
    Route::middleware('inventory.auth')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('inventory.dashboard');


        Route::resource('callnumber-issues', CallnumberIssueController::class);


        Route::post('/logout', [InventoryAuthController::class, 'logout'])
            ->name('inventory.logout');

        Route::resource('products', ProductController::class);
        Route::post('/products/update-stock', [ProductController::class, 'updateStock'])
            ->name('products.updateStock');

        Route::post('/products/RTO-stock', [ProductController::class, 'rtoStock'])
            ->name('products.rtoStock');

        Route::resource('categories', CategoryController::class);
        Route::resource('warehouses', WarehouseController::class);

        Route::resource('suppliers', SupplierController::class);
        Route::resource('purchases', PurchaseController::class);
        Route::resource('stocks', StockController::class)->only(['index']);
        Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
            ->name('sales.invoice');



        Route::resource('sales', SaleController::class);
        Route::get('/salesreport', [SaleController::class, 'salesreport'])
            ->name('sales.report');
        Route::get('/productreport', [ProductController::class, 'productreport'])
            ->name('products.report');

        Route::get('/RTOreport', [ProductController::class, 'rtoreport'])
            ->name('rto.report');
        Route::post('/rto-restock', [ProductController::class, 'rtoRestock'])->name('rto.restock');

        Route::get(
            '/productreport/export',
            [ProductController::class, 'exportProductReport']
        )->name('products.report.export');

        Route::get(
            '/rtoreport/export',
            [ProductController::class, 'exportRTOReport']
        )->name('rto.report.export');
        Route::get(
            '/salesreport/export',
            [SaleController::class, 'exportsalesReport']
        )->name('sales.report.export');
    });
});
