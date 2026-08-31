<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Jobs\DelhiveryTrackingJob;
use App\Models\Shipment;
use Illuminate\Console\Scheduling\Schedule;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->validateCsrfTokens(except: [
            'whatsapp/webhook/*',
        ]);

        $middleware->alias([
            'calling_user' => \App\Http\Middleware\CallingUserAuth::class,
            'inventory.auth' => \App\Http\Middleware\InventoryAuth::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'role'   => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function ($schedule) {

        $schedule->command('sync:shopify-orders')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('sync:shopify-abandoned-checkouts')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('orders:run-assignment-schedulers')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('shopify:refresh-tokens')
            ->hourly()
            ->withoutOverlapping();

        $schedule->call(function () {

            Shipment::where('courier', 'delhivery')
                ->whereNotNull('awb')
                ->whereNotIn('status', [
                    'delivered',
                    'returned',
                ])
                ->pluck('id')
                ->each(function ($shipmentId) {

                    DelhiveryTrackingJob::dispatch(
                        (int) $shipmentId
                    );
                });
        })
            ->name('delhivery-tracking-sync')
            ->everyMinute()
            ->withoutOverlapping();
    })

    ->withExceptions(function (Exceptions $exceptions): void {})->create();
