<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopifyImportController;
use App\Http\Controllers\ClientProductController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PostOfficeExportController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ShopifyOrderController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])
        ->name('dashboard');

    Route::get('/labelsenders', [AdminController::class, 'labelsenders'])
        ->name('labelsenders');

    Route::post('/labelsenders', [AdminController::class, 'storeLabelSenders'])
        ->name('labelsenders.store');

    Route::get('/labelgenrate', [AdminController::class, 'labelgenrate'])
        ->name('labelgenrate');


    Route::get('/labelgenerate', [ShopifyImportController::class, 'popup']);
    Route::post('/shopify-import', [ShopifyImportController::class, 'import']);
    Route::post('/barcode-save', [ShopifyImportController::class, 'save']);


    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');


    Route::get('/client-products', [ClientProductController::class, 'index'])
        ->name('client.products');

    Route::post('/client-products', [ClientProductController::class, 'store'])
        ->name('client.products.store');

    Route::delete('/client-products/{id}', [ClientProductController::class, 'delete'])
        ->name('client.products.delete');


    Route::get('/shopify/import', [ShopifyController::class, 'importPage'])->name('shopify.import.page');
    Route::post('/shopify/import', [ShopifyController::class, 'importExcel'])->name('shopify.import');

    Route::get('/shopify/orders', [ShopifyController::class, 'orders']);
    Route::post('/orders/{id}/assign-barcode', [ShopifyController::class, 'assignBarcode'])
        ->name('orders.assign-barcode');

    Route::get('/barcodes', [BarcodeController::class, 'index'])
        ->name('barcodes');

    Route::post('/barcodes/import', [BarcodeController::class, 'import'])
        ->name('barcodes.import');
    Route::post('/admin/download-barcodes', [OrderController::class, 'downloadBarcodes'])
        ->name('admin.download.barcodes');

    Route::delete(
        '/admin/orders/delete',
        [OrderController::class, 'deleteOrdersWithLog']
    )->name('admin.orders.delete');


    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.list');


    Route::get('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::post('/record/store', [RecordController::class, 'store'])->name('record.store');
    Route::get(
        '/get-client-products/{clientId}',
        [RecordController::class, 'getClientProducts']
    )->name('client.products');
    Route::post(
        '/whatsapp-excel-import',
        [ShopifyOrderController::class, 'whatsappExcelImport']
    )->name('whatsapp.excel.import');



    Route::get('/orders/import', [OrderController::class, 'importForm'])
        ->name('orders.import');

    Route::post('/orders/import', [OrderController::class, 'importExcel'])
        ->name('orders.import.post');


    Route::get('/export-post-office', [PostOfficeExportController::class, 'export'])
        ->name('postoffice.export');

    Route::get('/labels', [OrderController::class, 'labelIndex'])
        ->name('labels.index');

    Route::get('/labels/final-export', [OrderController::class, 'finalLabelExport'])
        ->name('labels.final.export');
    Route::post('/labels/export', [LabelController::class, 'export'])
        ->name('labels.export');
});


/*
|--------------------------------------------------------------------------
| Auth Routes (Login, Register, etc.)
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
