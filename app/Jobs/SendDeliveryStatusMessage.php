<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDeliveryStatusMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $orderId,
        public string $status,
        public string $provider
    ) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if (!$order) {
            Log::warning('DELIVERY MESSAGE ORDER NOT FOUND', [
                'order_id' => $this->orderId,
                'status' => $this->status,
                'provider' => $this->provider,
            ]);

            return;
        }

        if ($this->provider === 'aisency') {

            try {

                Log::info('AI SENCY JOB STARTED', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                ]);

                $sent = app(
                    \App\Services\AiSencyService::class
                )->pushStatus(
                    $order,
                    $this->status
                );

                if ($sent) {

                    Log::info('AI SENCY DELIVERY MESSAGE SENT', [
                        'order_id' => $order->order_id,
                        'client_id' => $order->client_id,
                        'barcode' => $order->barcode,
                        'status' => $this->status,
                    ]);

                    return;
                }

                Log::error('AI SENCY DELIVERY MESSAGE FAILED', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                    'attempt' => $this->attempts(),
                ]);

                throw new \RuntimeException(
                    "AiSency message sending failed for order {$order->order_id}, status {$this->status}"
                );
            } catch (Throwable $e) {

                Log::error('AI SENCY JOB EXCEPTION', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                    'attempt' => $this->attempts(),
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }


        if ($this->provider === 'bytespeed') {

            try {

                Log::info('BYTESPEED JOB STARTED', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                ]);

                $sent = app(
                    \App\Services\ByteSpeedService::class
                )->pushStatus(
                    $order,
                    $this->status
                );

                if ($sent) {

                    Log::info('BYTESPEED DELIVERY MESSAGE SENT', [
                        'order_id' => $order->order_id,
                        'client_id' => $order->client_id,
                        'barcode' => $order->barcode,
                        'status' => $this->status,
                    ]);

                    return;
                }

                Log::error('BYTESPEED DELIVERY MESSAGE FAILED', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                    'attempt' => $this->attempts(),
                ]);

                throw new \RuntimeException(
                    "ByteSpeed message sending failed for order {$order->order_id}, status {$this->status}"
                );
            } catch (Throwable $e) {

                Log::error('BYTESPEED JOB EXCEPTION', [
                    'order_id' => $order->order_id,
                    'client_id' => $order->client_id,
                    'barcode' => $order->barcode,
                    'status' => $this->status,
                    'attempt' => $this->attempts(),
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        Log::warning('UNKNOWN DELIVERY MESSAGE PROVIDER', [
            'order_id' => $order->order_id,
            'status' => $this->status,
            'provider' => $this->provider,
        ]);
    }
}
