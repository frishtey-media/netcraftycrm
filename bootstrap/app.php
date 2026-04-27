<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'calling_user' => \App\Http\Middleware\CallingUserAuth::class,
            'inventory.auth' => \App\Http\Middleware\InventoryAuth::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('sync:shopify-orders')
            ->everyMinute()
            ->withoutOverlapping();
        //->withSchedule(function ($schedule) {
        //   $schedule->command('sync:shopify-orders')
        //      ->dailyAt('17:00') // 5 PM
        //   ->withoutOverlapping();
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
