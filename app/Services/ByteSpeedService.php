<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ByteSpeedService
{
    public function pushStatus(Order $order, string $status): bool
    {
        // ONLY CLIENT 5
        if ((int) $order->client_id !== 5) {
            return false;
        }

        $url = config('services.bytespeed.url');
        $token = config('services.bytespeed.token');

        if (!$url || !$token) {
            Log::warning('ByteSpeed configuration missing', [
                'order_id' => $order->order_id,
                'status'   => $status,
            ]);

            return false;
        }

        $payload = $this->buildPayload($order, $status);

        try {

            $response = Http::timeout(20)
                ->withToken($token)
                ->acceptJson()
                ->post($url, $payload);

            Log::info('ByteSpeed Push', [
                'order_id' => $order->order_id,
                'status'   => $status,
                'http_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {

            Log::error('ByteSpeed Push Failed', [
                'order_id' => $order->order_id,
                'status'   => $status,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function buildPayload(Order $order, string $status): array
    {
        return [
            'event' => $status,

            'customer' => [
                'name'  => $order->customer_name,
                'phone' => $order->customer_phone,
            ],

            'order' => [
                'order_id' => $order->order_id,
                'product'  => $order->product,
                'quantity' => $order->quantity,
            ],

            'shipping' => [
                'tracking_id' => $order->barcode,
                'tracking_url' => $order->barcode
                    ? 'https://myspeedpost.com/speed-post-tracking?n='
                    . urlencode($order->barcode)
                    . '&sync=true'
                    : null,

                'address' => [
                    'address' => $order->address ?? null,
                    'city'    => $order->city ?? null,
                    'state'   => $order->state ?? null,
                    'pincode' => $order->pincode ?? null,
                ],
            ],

            'payment' => [
                'type' => $order->payment_mode,
            ],

            'status' => [
                'code' => $status,
                'name' => $this->statusName($status),
            ],

            'crm' => [
                'client_id' => $order->client_id,
                'source'    => 'ourcrm.netcrafty.com',
            ],
        ];
    }

    private function statusName(string $status): string
    {
        return match ($status) {
            'shipped'         => 'Shipped',
            'in_transit'      => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'on_hold'         => 'On Hold',
            'delivered'       => 'Delivered',
            default           => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
