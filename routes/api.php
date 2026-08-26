<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CallingOrderApiController;

Route::get('/verified-orders', [
    CallingOrderApiController::class,
    'verifiedOrders'
]);

Route::post('/knowlarity/log', [CallingOrderApiController::class, 'store']);
