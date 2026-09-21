<?php

namespace App\Jobs;

use App\Models\ShipmentStatusLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendByteSpeedStatusWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $logId
    ) {}

    public function handle(): void
    {
        $log = ShipmentStatusLog::with('order')->find($this->logId);

        if (!$log) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Already successful
        |--------------------------------------------------------------------------
        */

        if ($log->webhook_status === 'success') {
            return;
        }

        $order = $log->order;

        if (!$order) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Status → Event + Template
        |--------------------------------------------------------------------------
        */

        $mapping = [

            'order_booked' => [
                'event' => 'order.booked',
                'template' => 'BOOKED_CONFIRMED',
            ],

            'label_created' => [
                'event' => 'order.shipped',
                'template' => 'SHIPPED',
            ],

            'shipped' => [
                'event' => 'order.shipped',
                'template' => 'SHIPPED',
            ],

            'in_transit' => [
                'event' => 'order.in_transit',
                'template' => 'IN_TRANSIT',
            ],

            'out_for_delivery' => [
                'event' => 'order.out_for_delivery',
                'template' => 'OUT_FOR_DELIVERY',
            ],

            'on_hold' => [
                'event' => 'order.on_hold',
                'template' => 'ON_HOLD',
            ],

            'delivered' => [
                'event' => 'order.delivered',
                'template' => 'DELIVERED',
            ],
        ];

        if (!isset($mapping[$log->status])) {
            return;
        }

        $event = $mapping[$log->status];

        $awb = $order->barcode;

        $trackingUrl =
            'https://myspeedpost.com/speed-post-tracking?n=' .
            urlencode($awb) .
            '&sync=true';

        /*
        |--------------------------------------------------------------------------
        | JSON Payload
        |--------------------------------------------------------------------------
        */

        $payload = [

            'event' => $event['event'],

            'event_id' => $log->event_id,

            'template_name' => $event['template'],

            'customer_name' => $order->customer_name,

            'customer_phone' => $order->customer_phone,

            'order_id' => $order->order_id,

            'product_name' => $order->product,

            'tracking_id' => $awb,

            'tracking_url' => $trackingUrl,

            'payment_type' =>
            strtolower($order->payment_mode ?? '') === 'cod'
                ? 'COD'
                : 'Prepaid',

            'timestamp' => now()->toISOString(),
        ];

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
        );

        /*
        |--------------------------------------------------------------------------
        | HMAC Signature
        |--------------------------------------------------------------------------
        */

        $signature =
            'sha256=' .
            hash_hmac(
                'sha256',
                $jsonPayload,
                config('services.bytespeed.secret')
            );

        /*
        |--------------------------------------------------------------------------
        | Send
        |--------------------------------------------------------------------------
        */

        $log->increment('attempts');

        try {

            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',

                    'X-Webhook-Event' =>
                    $event['event'],

                    'X-Webhook-Event-ID' =>
                    $log->event_id,

                    'X-Webhook-Signature' =>
                    $signature,
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post(
                    config('services.bytespeed.webhook_url')
                );

            $log->update([

                'response_status' =>
                $response->status(),

                'response_body' =>
                $response->body(),

                'webhook_status' =>
                $response->successful()
                    ? 'success'
                    : 'failed',

                'sent_at' =>
                $response->successful()
                    ? now()
                    : null,
            ]);

            if (!$response->successful()) {
                throw new \Exception(
                    'ByteSpeed webhook failed: ' .
                        $response->status()
                );
            }
        } catch (\Throwable $e) {

            $log->update([
                'webhook_status' => 'failed',
                'response_body' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
