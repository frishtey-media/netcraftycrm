<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSencyService
{
    public function pushStatus(Order $order, string $status): bool
    {


        if ((int) $order->client_id !== 2) {
            return false;
        }



        $url = config('services.ai_sency.url');
        $apiKey = config('services.ai_sency.token');

        $campaign = config(
            'services.ai_sency.campaigns.' . $status
        );

        if (
            empty($url) ||
            empty($apiKey) ||
            empty($campaign)
        ) {
            Log::warning('Ai Sency configuration missing', [
                'order_id' => $order->order_id,
                'status' => $status,
                'url_exists' => !empty($url),
                'api_key_exists' => !empty($apiKey),
                'campaign_exists' => !empty($campaign),
            ]);

            return false;
        }

        $phone = preg_replace(
            '/\D+/',
            '',
            (string) $order->customer_phone
        );

        if (empty($phone)) {
            Log::warning('Ai Sency customer phone missing', [
                'order_id' => $order->order_id,
                'status' => $status,
            ]);

            return false;
        }

        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        /*
        |--------------------------------------------------------------------------
        | TRACKING URL
        |--------------------------------------------------------------------------
        */

        $trackingUrl = '';

        if (!empty($order->barcode)) {
            $trackingUrl =
                'https://myspeedpost.com/speed-post-tracking?n='
                . urlencode($order->barcode)
                . '&sync=true';
        }

        /*
        |--------------------------------------------------------------------------
        | BASIC DATA
        |--------------------------------------------------------------------------
        */

        $customerName = (string) (
            $order->customer_name ?? 'Customer'
        );

        $orderId = (string) (
            $order->order_id ?? ''
        );

        $productName = (string) (
            $order->product ?? ''
        );

        $paymentType = (string) (
            $order->payment_mode ?? ''
        );

        $barcode = (string) (
            $order->barcode ?? ''
        );

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE PARAMS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | These mappings are based on the templates already confirmed:
        |
        | shipped      = 6 params
        | in_transit   = 5 params
        | out_for_delivery = 6 params
        |
        | ON HOLD and DELIVERED MUST match their actual AiSensy
        | template variables.
        |
        |--------------------------------------------------------------------------
        */

        $templateParams = match ($status) {

            /*
            |--------------------------------------------------------------------------
            | SHIPPED
            |--------------------------------------------------------------------------
            */

            'shipped' => [
                $customerName,
                $orderId,
                $productName,
                $paymentType,
                $barcode,
                $trackingUrl,
            ],

            /*
            |--------------------------------------------------------------------------
            | IN TRANSIT
            |--------------------------------------------------------------------------
            |
            | Your tested campaign accepts 5 parameters.
            |
            */

            'in_transit' => [
                $customerName,
                $orderId,
                $productName,
                $barcode,
                $trackingUrl,
            ],

            /*
            |--------------------------------------------------------------------------
            | OUT FOR DELIVERY
            |--------------------------------------------------------------------------
            |
            | Current tested campaign is accepting the existing 6-param
            | structure.
            |
            */

            'out_for_delivery' => [
                $customerName,
                $orderId,
                $productName,
                $paymentType,
                $barcode,
                $trackingUrl,
            ],

            /*
            |--------------------------------------------------------------------------
            | ON HOLD
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Current 6-param mapping gives 400.
            |
            | Do NOT assume the template variables.
            |
            | Temporarily use the same basic 5-param structure as the
            | In Transit template ONLY if your On Hold template has:
            |
            | {{1}} Customer
            | {{2}} Order ID
            | {{3}} Product
            | {{4}} Tracking ID
            | {{5}} Tracking URL
            |
            */

            'on_hold' => [
                $customerName,
                (string) ($order->delivery_remark ?? ''),
                $orderId,
                $productName,
                $barcode,
                $trackingUrl,
            ],

            /*
            |--------------------------------------------------------------------------
            | DELIVERED
            |--------------------------------------------------------------------------
            |
            | Same warning applies here.
            |
            | This is a 5-param structure:
            |
            | Customer
            | Order ID
            | Product
            | Tracking ID
            | Tracking URL
            |
            */

            'delivered' => [
                $customerName,
                $orderId,
                $productName,
                $barcode,
                $trackingUrl,
            ],

            default => [],
        };

        /*
        |--------------------------------------------------------------------------
        | VALIDATE PARAMS
        |--------------------------------------------------------------------------
        */

        if (empty($templateParams)) {
            Log::warning('Ai Sency unsupported status', [
                'order_id' => $order->order_id,
                'status' => $status,
            ]);

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | PAYLOAD
        |--------------------------------------------------------------------------
        */

        $payload = [
            'apiKey' => $apiKey,

            'campaignName' => $campaign,

            'destination' => $phone,

            'userName' => $customerName,

            'source' => 'CRM',

            'templateParams' => $templateParams,
        ];

        /*
        |--------------------------------------------------------------------------
        | LOG PARAM COUNT
        |--------------------------------------------------------------------------
        */

        Log::info('Ai Sency Request', [
            'order_id' => $order->order_id,
            'status' => $status,
            'campaign' => $campaign,
            'destination' => $phone,
            'template_param_count' => count($templateParams),
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

        try {

            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            Log::info('Ai Sency Push', [
                'order_id' => $order->order_id,
                'status' => $status,
                'http_code' => $response->status(),
                'response' => $response->json(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            if ($response->successful()) {

                Log::info('Ai Sency Push Success', [
                    'order_id' => $order->order_id,
                    'status' => $status,
                    'campaign' => $campaign,
                ]);

                return true;
            }

            Log::error('Ai Sency Push Failed', [
                'order_id' => $order->order_id,
                'status' => $status,
                'campaign' => $campaign,
                'http_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {

            Log::error('Ai Sency Exception', [
                'order_id' => $order->order_id,
                'status' => $status,
                'campaign' => $campaign,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
