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
use App\Http\Controllers\RTOController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MoneyorderExportController;
use App\Http\Controllers\convertAmazonToTally;
use App\Models\CallingOrder;
use App\Http\Controllers\CallingUserAuthController;
use App\Http\Controllers\CallingUserController;
use App\Http\Controllers\CallingOrderController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\StaffChatController;



require __DIR__ . '/inventory.php';

Route::get('/', function () {
    return redirect()->route('login');
});
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/ordersdashboard', [AdminController::class, 'ordersdashboard'])->name('ordersdashboard');
    Route::get('/labelsenders', [AdminController::class, 'labelsenders'])->name('labelsenders');
    Route::post('/labelsenders', [AdminController::class, 'storeLabelSenders'])->name('labelsenders.store');
    Route::get('/labelgenrate', [AdminController::class, 'labelgenrate'])->name('labelgenrate');
    Route::post('/amazon-to-tally', [convertAmazonToTally::class, 'amazonToTally']);
    Route::get('/labelgenerate', [ShopifyImportController::class, 'popup']);
    Route::post('/shopify-import', [ShopifyImportController::class, 'import']);
    Route::post('/barcode-save', [ShopifyImportController::class, 'save']);
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/client-products', [ClientProductController::class, 'index'])->name('client.products');
    Route::post('/client-products', [ClientProductController::class, 'store'])->name('client.products.store');
    Route::delete('/client-products/{id}', [ClientProductController::class, 'delete'])->name('client.products.delete');
    Route::get('/shopify/import', [ShopifyController::class, 'importPage'])->name('shopify.import.page');
    Route::post('/shopify/import', [ShopifyController::class, 'importExcel'])->name('shopify.import');
    Route::get('/shopify/orders', [ShopifyController::class, 'orders']);
    Route::post('/orders/{id}/assign-barcode', [ShopifyController::class, 'assignBarcode'])->name('orders.assign-barcode');
    Route::get('/barcodes', [BarcodeController::class, 'index'])->name('barcodes');
    Route::post('/barcodes/import', [BarcodeController::class, 'import'])->name('barcodes.import');

    Route::post('/admin/download-barcodes', [OrderController::class, 'downloadBarcodes'])->name('admin.download.barcodes');

    Route::post('/download-barcodes-excel', [OrderController::class, 'downloadBarcodesexcel'])
        ->name('admin.download.barcodesexcel');

    Route::post('/clear-duplicate-orders', [PostOfficeExportController::class, 'clearDuplicates'])
        ->name('clear.duplicates');
    Route::get('/export', [PostOfficeExportController::class, 'export'])->name('export.orders');

    Route::delete('/admin/orders/delete', [OrderController::class, 'deleteOrdersWithLog'])->name('admin.orders.delete');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.list');
    Route::get('/orders/import', [OrderController::class, 'importForm'])->name('orders.import');
    Route::post('/orders/import', [OrderController::class, 'importExcel'])->name('orders.import.post');
    Route::get('/labels', [OrderController::class, 'labelIndex'])->name('labels.index');
    Route::get('/labels/final-export', [OrderController::class, 'finalLabelExport'])->name('labels.final.export');
    Route::get('/export-post-office', [PostOfficeExportController::class, 'export'])->name('postoffice.export');
    Route::post('/whatsapp-excel-import', [ShopifyOrderController::class, 'whatsappExcelImport'])->name('whatsapp.excel.import');
    Route::get('/rto', [RTOController::class, 'index'])->name('rto.index');
    Route::post('/rto-search', [RTOController::class, 'search'])->name('rto.search');
    Route::get('/rto-export', [RTOController::class, 'export'])->name('rto.export');
    Route::get('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::post('/record/store', [RecordController::class, 'store'])->name('record.store');
    Route::get('/get-client-products/{clientId}', [RecordController::class, 'getClientProducts'])->name('client.products');
    Route::post('/labels/export', [LabelController::class, 'export'])->name('labels.export');
    Route::post('labels/selected-pdf', [LabelController::class, 'exportSelected'])->name('labels.selected.pdf');
    Route::get('/Invoice', [InvoiceController::class, 'InvoiceIndex'])->name('Invoice.index');
    Route::post('/seller/store', [InvoiceController::class, 'storeSeller'])->name('seller.store');
    Route::post('/invoice/import', [InvoiceController::class, 'importExcel'])->name('invoice.import');
    // Route::get('orders/invoice-pdf', [OrderController::class, 'invoicePdf'])->name('orders.invoice.pdf');
    Route::get('orders/selected-invoice-pdf', [InvoiceController::class, 'downloadSelectedInvoices'])->name('orders.invoice.pdf');
    //Route::get('orders/label-pdf', [OrderController::class, 'labelPdf'])->name('orders.label.pdf');
    Route::get('orders/postoffice-excel', [PostOfficeExportController::class, 'postOfficeExcel'])->name('orders.postoffice.excel');
    Route::post('orders/moneyorder-pdf', [MoneyorderExportController::class, 'Moneyorder'])->name('orders.Moneyorder.pdf');
    Route::post('/assign-orders', [AdminController::class, 'assignOrders'])->name('assign.orders');
    Route::get('/assign', [AdminController::class, 'assignPage']);
    Route::get('/calling-users', [CallingUserController::class, 'index'])->name('calling.users');
    Route::post('/calling-users', [CallingUserController::class, 'store'])->name('calling.users.store');
    Route::get('/calling-users/toggle/{id}', [CallingUserController::class, 'toggle'])->name('calling.users.toggle');
    Route::get('/performance', [AdminController::class, 'performance'])
        ->name('performance.dashboard');

    // GET → form open
    Route::get('/client_assign_staff', [ClientController::class, 'clientStaffForm'])
        ->name('client_staff_form');

    // POST → save mapping
    Route::post('/client_assign_staff.', [ClientController::class, 'saveClientStaff'])
        ->name('client_staff_save');

    Route::get('/staff-verified', [AdminController::class, 'staffVerified'])
        ->name('admin.staff.verified');

    Route::get('/staff-verified-export', [AdminController::class, 'staffVerifiedExport'])
        ->name('admin.staff.verified.export');
    Route::post('/shift-orders', [AdminController::class, 'shiftOrders'])
        ->name('shift.orders');


    Route::get('/test-order', function () {
        CallingOrder::create([
            'client_id' => 1,
            'order_id' => 'TEST123',
            'order_date' => now(),
            'product_name' => 'Test Product',
            'quantity' => 1,
            'customer_name' => 'Test User',
            'customer_phone' => '9999999999',
            'shipping_address' => 'Test Address',
            'city' => 'Test City',
            'amount' => 500,
        ]);

        return "Inserted";
    });


    Route::get('/phpinfo', function () {
        phpinfo();
    });
});
Route::get('/calling/login', [CallingUserAuthController::class, 'showLogin'])->name('calling.login');
Route::post('/calling/login', [CallingUserAuthController::class, 'login'])->name('calling.login.post');
Route::middleware('calling_user')->group(function () {
    Route::get('/calling/dashboard', [CallingUserAuthController::class, 'dashboard'])
        ->name('calling.dashboard');
    Route::get('/calling/orders', [CallingUserAuthController::class, 'orders'])
        ->name('calling.orders');



    Route::get('/calling/verified', [CallingUserAuthController::class, 'verified'])
        ->name('calling.verified');


    Route::get('/calling/same_order', [CallingUserAuthController::class, 'same_order'])
        ->name('calling.same_order');


    Route::get('/calling/cancel', [CallingUserAuthController::class, 'cancel'])
        ->name('calling.cancel');



    Route::get('/calling/not_reachable', [CallingUserAuthController::class, 'not_reachable'])
        ->name('calling.not_reachable');
    //Route::get('/calling/verified', [CallingUserAuthController::class, 'verified'])->name('calling.verified');
    Route::get('/calling/not-reachable', [CallingUserAuthController::class, 'notReachable'])->name('calling.not.reachable');
    Route::post('/calling/update/{id}', [CallingUserAuthController::class, 'update'])
        ->middleware('calling_user');
    Route::post('/calling/statusupdate/{id}', [CallingUserAuthController::class, 'statusupdate'])
        ->middleware('calling_user');
    Route::get('/calling/manual-order', [CallingOrderController::class, 'create'])->name('calling.manual');
    Route::post('/calling/manual-order', [CallingOrderController::class, 'store'])->name('calling.manual.store');
    Route::get('/calling/whatsapp-orders', [CallingOrderController::class, 'whatsappOrders'])->name('calling.whatsapp');
    Route::post('/calling/logout', [CallingUserAuthController::class, 'logout'])
        ->name('calling.logout');


    Route::get('/calling/inbox', [StaffChatController::class, 'inbox'])->name('calling.inbox');

    Route::get('/calling/chat/{id}', [StaffChatController::class, 'chat'])->name('calling.chat');

    Route::post('/calling/send', [StaffChatController::class, 'send'])->name('calling.send');
});
/*
|--------------------------------------------------------------------------
| Auth Routes (Login, Register, etc.)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
