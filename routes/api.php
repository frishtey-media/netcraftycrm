<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CallingOrderApiController;
use App\Http\Controllers\Api\CallingUserAuthController;

Route::post('/staff/login', [
    CallingUserAuthController::class,
    'login'
]);

Route::post('/knowlarity/log', [
    CallingOrderApiController::class,
    'store'
]);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/staff/me', [
        CallingUserAuthController::class,
        'me'
    ]);

    Route::post('/staff/logout', [
        CallingUserAuthController::class,
        'logout'
    ]);

    Route::get('/staff/dashboard', [
        CallingOrderApiController::class,
        'dashboard'
    ]);


    Route::get('/verified-orders', [
        CallingOrderApiController::class,
        'verifiedOrders'
    ]);

    Route::get('/same-orders', [
        CallingOrderApiController::class,
        'sameOrders'
    ]);

    Route::get('/out-for-delivery', [
        CallingOrderApiController::class,
        'outForDelivery'
    ]);

    Route::get('/on-hold', [
        CallingOrderApiController::class,
        'onHold'
    ]);

    Route::get('/all-orders', [
        CallingOrderApiController::class,
        'allOrders'
    ]);
    Route::get('/staff/sidebar-counts', [
        CallingOrderApiController::class,
        'sidebarCounts'
    ]);

    Route::get('/pending-orders', [
        CallingOrderApiController::class,
        'pendingOrders'
    ]);

    Route::post('/pending-orders/{id}/status', [
        CallingOrderApiController::class,
        'updatePendingOrderStatus'
    ]);

    Route::put('/pending-orders/{id}', [
        CallingOrderApiController::class,
        'updatePendingOrder'
    ]);
    Route::post('/track-call', [
        CallingOrderApiController::class,
        'trackCall'
    ]);

    Route::get('/prepaid-orders', [
        CallingOrderApiController::class,
        'prepaidOrders'
    ]);

    Route::post('/prepaid-orders/{id}/status', [
        CallingOrderApiController::class,
        'prepaidOrderStatus'
    ]);

    Route::put('/prepaid-orders/{id}', [
        CallingOrderApiController::class,
        'prepaidOrderUpdate'
    ]);

    Route::get('/web-orders', [
        CallingOrderApiController::class,
        'webOrders'
    ]);

    Route::post('/web-orders/{id}/status', [
        CallingOrderApiController::class,
        'webOrderStatus'
    ]);

    Route::put('/web-orders/{id}', [
        CallingOrderApiController::class,
        'webOrderUpdate'
    ]);

    Route::get('/rto-orders', [
        CallingOrderApiController::class,
        'rtoOrders'
    ]);

    Route::post('/rto-orders/{id}/status', [
        CallingOrderApiController::class,
        'rtoOrderStatus'
    ]);

    Route::put('/rto-orders/{id}', [
        CallingOrderApiController::class,
        'rtoOrderUpdate'
    ]);

    Route::get('/deliver-orders', [
        CallingOrderApiController::class,
        'deliverOrders'
    ]);

    Route::post('/deliver-orders/{id}/status', [
        CallingOrderApiController::class,
        'deliverOrderStatus'
    ]);

    Route::put('/deliver-orders/{id}', [
        CallingOrderApiController::class,
        'deliverOrderUpdate'
    ]);

    Route::get('/abandoned-orders', [
        CallingOrderApiController::class,
        'abandonedOrders'
    ]);

    Route::post('/abandoned-orders/{id}/status', [
        CallingOrderApiController::class,
        'abandonedOrderStatus'
    ]);

    Route::put('/abandoned-orders/{id}', [
        CallingOrderApiController::class,
        'abandonedOrderUpdate'
    ]);
    Route::get('/verified-orders', [
        CallingOrderApiController::class,
        'verifiedOrders'
    ]);
});
