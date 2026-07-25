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
use App\Http\Controllers\CallingUserAuthController;
use App\Http\Controllers\CallingUserController;
use App\Http\Controllers\CallingOrderController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\StaffChatController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ReportController;
use App\Models\CallingOrder;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExtreportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RepeatCustomerController;

//dd(base_path());

require __DIR__ . '/inventory.php';

Route::get('/', function () {
    return redirect()->route('login');
});


//Route::get('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
//Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);


Route::match(
    ['GET', 'POST'],
    '/whatsapp/webhook/{client}',
    [WhatsAppController::class, 'webhook']
);

//Route::post('/webhook/shopify/order', [WhatsAppController::class, 'orderCreate']);




Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');


    Route::get('/dashboard/orders', [AdminController::class, 'dashboardOrders'])
        ->name('dashboard.orders');


    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users/store', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{id}/edit', [UserController::class, 'edit'])
        ->name('users.edit');

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->name('users.update');
    Route::get('/generate-order-id/{staffId}', [RecordController::class, 'generateOrderId']);

    Route::get('/generate-order-id', [RecordController::class, 'generateOrderId'])
        ->name('generate.order.id');

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

    Route::get('/barcodesview', [BarcodeController::class, 'indexbarcode']);

    Route::get('/download-barcodes', [BarcodeController::class, 'download'])
        ->name('download.barcodes');

    Route::post('/admin/download-barcodes', [OrderController::class, 'downloadBarcodes'])->name('admin.download.barcodes');

    Route::post('/download-barcodes-excel', [OrderController::class, 'downloadBarcodesexcel'])
        ->name('admin.download.barcodesexcel');

    Route::post('/clear-duplicate-orders', [PostOfficeExportController::class, 'clearDuplicates'])
        ->name('clear.duplicates');
    Route::get('/export', [PostOfficeExportController::class, 'export'])->name('export.orders');

    Route::delete('/admin/orders/delete', [OrderController::class, 'deleteOrdersWithLog'])->name('admin.orders.delete');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.list');


    Route::get(
        '/reports/delivered',
        [OrderController::class, 'deliverindex']
    )->name('delivered.index');

    Route::get(
        '/reports/delivered/export',
        [OrderController::class, 'deliverExport']
    )->name('delivered.export');


    Route::get(
        '/reports/staff-delivery-detail',
        [OrderController::class, 'staffDeliveryDetail']
    )->name('reports.staff.delivery.detail');


    Route::get(
        '/record/customer-history',
        [RepeatCustomerController::class, 'customerHistory']
    )->name('record.customer.history');


    Route::get(
        '/order/staff-summary/export',
        [OrderController::class, 'exportStaffSummary']
    )->name('order.staff.export');


    Route::get('/orders/import', [OrderController::class, 'importForm'])->name('orders.import');
    Route::post('/orders/import', [OrderController::class, 'importExcel'])->name('orders.import.post');


    Route::post('/orders/whstappimport', [RecordController::class, 'whstappimportOrders'])
        ->name('record.whstappimport');


    Route::get('/labels', [OrderController::class, 'labelIndex'])->name('labels.index');
    Route::get('/labels/final-export', [OrderController::class, 'finalLabelExport'])->name('labels.final.export');
    Route::post(
        '/export-post-office',
        [PostOfficeExportController::class, 'export']
    )->name('postoffice.export');
    Route::post('/whatsapp-excel-import', [ShopifyOrderController::class, 'whatsappExcelImport'])->name('whatsapp.excel.import');
    Route::get('/rto', [RTOController::class, 'index'])->name('rto.index');





    Route::post(
        '/orders/manual-delivery',
        [OrderController::class, 'manualDelivery']
    )->name('orders.manual.delivery');


    Route::post('/rto-search', [RTOController::class, 'search'])->name('rto.search');
    Route::get('/rto-export', [RTOController::class, 'export'])->name('rto.export');
    Route::get('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::post('/record/store', [RecordController::class, 'store'])->name('record.store');
    Route::get('/get-client-products/{clientId}', [RecordController::class, 'getClientProducts'])->name('client.products');

    Route::get('/record/generate-order-id/{staffId}', [RecordController::class, 'generateOrderId'])
        ->name('record.generateOrderId');

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

    Route::get('/get-products/{client}', [OrderController::class, 'getProducts'])
        ->name('get.products');

    Route::get(
        '/performance/orders',
        [AdminController::class, 'orderDetails']
    )->name('performance.orders');

    Route::prefix('reports')->group(function () {

        Route::get(
            '/repeat-rto',
            [RepeatCustomerController::class, 'repeatRto']
        )
            ->name('reports.repeat.rto');


        Route::get(
            '/repeat-rto/{phone}',
            [RepeatCustomerController::class, 'repeatRtoDetail']
        )
            ->name('reports.repeat.rto.detail');
    });


    Route::get('/reports/{type}', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/export/{type}', [ReportController::class, 'export'])
        ->name('reports.export');
    Route::get('/staff-report/{staff_id}', [ReportController::class, 'staffReport'])
        ->name('staff.report');

    Route::get('/delivery', [DeliveryController::class, 'index'])
        ->name('delivery.index');

    Route::post('/delivery/upload', [DeliveryController::class, 'upload'])
        ->name('delivery.upload');

    Route::post('/payment-upload', [DeliveryController::class, 'paymentupload'])
        ->name('delivery.paymentupload');


    Route::get('/payments', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::get('/payments/export', [PaymentController::class, 'export'])
        ->name('payments.export');
    Route::get(
        '/payments/pending',
        [PaymentController::class, 'pendingPayment']
    )->name('payments.pending');

    Route::get(
        '/payments/pending/export',
        [PaymentController::class, 'pendingPaymentExport']
    )->name('payments.pending.export');


    Route::post('/orders/export-selected', [OrderController::class, 'exportSelected'])
        ->name('orders.export.selected');

    Route::post('/rto-received-upload', [DeliveryController::class, 'rtoReceivedUpload'])
        ->name('delivery.rtoReceivedUpload');

    Route::get('/delivery/report/{type}', [DeliveryController::class, 'report'])
        ->name('delivery.report');

    Route::get(
        '/delivery-export/{type}',
        [DeliveryController::class, 'export']
    )->name('delivery.export');

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


Route::middleware(['auth', 'client'])
    ->prefix('client')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('client.dashboard');
        });

        Route::get('/orders', [OrderController::class, 'clientOrders']);
    });


Route::get('/calling/login', [CallingUserAuthController::class, 'showLogin'])->name('calling.login');
Route::post('/calling/login', [CallingUserAuthController::class, 'login'])->name('calling.login.post');
Route::middleware('calling_user')->group(function () {
    Route::get('/calling/dashboard', [CallingUserAuthController::class, 'dashboard'])
        ->name('calling.dashboard');
    Route::get('/calling/orders', [CallingUserAuthController::class, 'orders'])
        ->name('calling.orders');



    Route::get(
        '/calling/customer-history',
        [RepeatCustomerController::class, 'customerHistory']
    )->name('calling.customer.history');

    Route::post(
        '/record/save-call-status',
        [RecordController::class, 'saveCallStatus']
    )->name('record.saveCallStatus');

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
    Route::get(
        '/calling/customer-history',
        [CallingOrderController::class, 'customerHistory']
    )->name('calling.customer.history');

    Route::get(
        '/calling/client-products/{clientId}',
        [CallingOrderController::class, 'getClientProducts']
    )->name('calling.client.products');

    Route::get(
        '/calling/preview-order-id',
        [CallingOrderController::class, 'previewOrderId']
    )->name('calling.preview.order.id');
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
