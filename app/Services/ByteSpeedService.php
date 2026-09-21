<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ByteSpeedService
{

    protected array $allowedStatuses = [
        'booked',
        'shipped',
        'in_transit',
        'out_for_delivery',
        'on_hold',
        'delivered',
        'delivery_thank_you',
        'repeat_reminder',
    ];

    public function pushStatus(Order $order, string $status): array
    {
        if ((int) $order->client_id !== 5) {
            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'ByteSpeed integration is only enabled for client 5.',
            ];
        }

        $status = strtolower(trim($status));

        if (!in_array($status, $this->allowedStatuses, true)) {
            throw new \InvalidArgumentException(
                "Unsupported ByteSpeed status: {$status}"
            );
        }

        $orderId = (string) (
            $order->order_id ?? $order->id
        );

        $customerName = trim(
            (string) ($order->customer_name ?? '')
        );

        $customerPhone = trim(
            (string) ($order->customer_phone ?? '')
        );

        $productName = trim(
            (string) ($order->product ?? '')
        );

        $paymentType = trim(
            (string) ($order->payment_mode ?? '')
        );

        $shippingAddress = trim(
            (string) ($order->shipping_address ?? '')
        );

        $city = trim(
            (string) ($order->city ?? '')
        );

        $state = trim(
            (string) ($order->state ?? '')
        );

        $pincode = trim(
            (string) ($order->pincode ?? '')
        );

        $trackingId = trim(
            (string) ($order->barcode ?? '')
        );

        $trackingUrl = '';

        if ($trackingId !== '') {
            $trackingUrl =
                'https://myspeedpost.com/speed-post-tracking?n=' .
                urlencode($trackingId) .
                '&sync=true';
        }

        $event = $this->getEventName($status);

        $templateName = $this->getTemplateName($status);

        $eventId = $this->generateEventId(
            $order,
            $status
        );

        $message = $this->getMessage(
            $order,
            $status,
            $customerName,
            $orderId,
            $productName,
            $paymentType,
            $trackingId,
            $trackingUrl
        );

        $payload = [

            'event' => $event,

            'event_id' => $eventId,

            'status' => $status,

            'template_name' => $templateName,

            'customer' => [

                'name' => $customerName,

                'phone' => $customerPhone,

                'address' => [

                    'shipping_address' => $shippingAddress,

                    'city' => $city,

                    'state' => $state,

                    'pincode' => $pincode,

                    'country' => 'India',
                ],
            ],

            'order' => [

                'id' => $orderId,

                'product_name' => $productName,

                'quantity' => $order->quantity ?? null,

                'payment_type' => $paymentType,
            ],


            'shipment' => [

                'tracking_id' => $trackingId,

                'tracking_url' => $trackingUrl,

                'delivery_date' => $order->delivery_date
                    ? (string) $order->delivery_date
                    : null,
            ],

            'message' => $message,
        ];

        if ($status === 'repeat_reminder') {

            $payload['trigger'] = [

                'type' => 'after_delivery',

                'days' => 20,
            ];
        }

        return $this->sendToByteSpeed($payload);
    }

    protected function getEventName(string $status): string
    {
        return match ($status) {

            'booked'
            => 'order.booked',

            'shipped'
            => 'order.shipped',

            'in_transit'
            => 'order.in_transit',

            'out_for_delivery'
            => 'order.out_for_delivery',

            'on_hold'
            => 'order.on_hold',

            'delivered'
            => 'order.delivered',

            'delivery_thank_you'
            => 'order.delivery_thank_you',

            'repeat_reminder'
            => 'order.repeat_reminder',

            default
            => throw new \InvalidArgumentException(
                "Invalid ByteSpeed status: {$status}"
            ),
        };
    }


    /**
     * Template name mapping.
     */
    protected function getTemplateName(string $status): string
    {
        return match ($status) {

            'booked'
            => 'BOOKED_CONFIRMED',

            'shipped'
            => 'SHIPPED',

            'in_transit'
            => 'IN_TRANSIT',

            'out_for_delivery'
            => 'OUT_FOR_DELIVERY',

            'on_hold'
            => 'ON_HOLD',

            'delivered'
            => 'DELIVERED',

            'delivery_thank_you'
            => 'DELIVERY_THANK_YOU',

            'repeat_reminder'
            => 'REPEAT_ORDER_20_DAYS',

            default
            => throw new \InvalidArgumentException(
                "Invalid ByteSpeed template: {$status}"
            ),
        };
    }


    /**
     * Generate unique event ID.
     */
    protected function generateEventId(
        Order $order,
        string $status
    ): string {

        $orderId = (string) (
            $order->order_id ?? $order->id
        );

        return 'order-' .
            $orderId .
            '-' .
            $status;
    }


    /**
     * Generate message.
     */
    protected function getMessage(
        Order $order,
        string $status,
        string $customerName,
        string $orderId,
        string $productName,
        string $paymentType,
        string $trackingId,
        string $trackingUrl
    ): string {

        return match ($status) {


            /*
            |--------------------------------------------------------------------------
            | BOOKED
            |--------------------------------------------------------------------------
            */

            'booked' =>

            "Hi {$customerName},\n\n" .

                "Your order has been confirmed successfully.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n" .

                "Payment: {$paymentType}\n\n" .

                "We’ll update you once your order has been shipped.\n\n" .

                "Thank you for shopping with us.",


            /*
            |--------------------------------------------------------------------------
            | SHIPPED
            |--------------------------------------------------------------------------
            */

            'shipped' =>

            "Hi {$customerName},\n\n" .

                "Your order has been shipped successfully.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n" .

                "Tracking ID: {$trackingId}\n" .

                "Courier: India Post\n\n" .

                "Track your shipment:\n" .

                "{$trackingUrl}",


            /*
            |--------------------------------------------------------------------------
            | IN TRANSIT
            |--------------------------------------------------------------------------
            */

            'in_transit' =>

            "Hi {$customerName},\n\n" .

                "Your order is currently in transit.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n" .

                "Tracking ID: {$trackingId}\n\n" .

                "Track your shipment:\n" .

                "{$trackingUrl}\n\n" .

                "Your order will reach you soon.",


            /*
            |--------------------------------------------------------------------------
            | OUT FOR DELIVERY
            |--------------------------------------------------------------------------
            */

            'out_for_delivery' =>

            "Hi {$customerName},\n\n" .

                "Your order is out for delivery today.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n" .

                "Tracking ID: {$trackingId}\n\n" .

                "Please keep your phone available to receive the order.\n\n" .

                "Track your shipment:\n" .

                "{$trackingUrl}",


            /*
            |--------------------------------------------------------------------------
            | ON HOLD
            |--------------------------------------------------------------------------
            */

            'on_hold' =>

            "Hi {$customerName},\n\n" .

                "Your order is currently on hold.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n" .

                "Tracking ID: {$trackingId}\n\n" .

                "We’ll update you once your shipment is moving again.\n\n" .

                "Track your shipment:\n" .

                "{$trackingUrl}\n\n" .

                "Thank you for your patience.",


            /*
            |--------------------------------------------------------------------------
            | DELIVERED
            |--------------------------------------------------------------------------
            */

            'delivered' =>

            "Hi {$customerName},\n\n" .

                "Your order has been delivered successfully.\n\n" .

                "Order ID: {$orderId}\n" .

                "Product: {$productName}\n\n" .

                "Thank you for shopping with us. " .

                "We hope you enjoy your product.",


            /*
            |--------------------------------------------------------------------------
            | DELIVERY THANK YOU
            |--------------------------------------------------------------------------
            */

            'delivery_thank_you' =>

            "Hi {$customerName},\n\n" .

                "Thank you for shopping with us.\n\n" .

                "We’re pleased to know that your order " .

                "{$orderId} has been delivered successfully.\n\n" .

                "We hope you enjoy {$productName}.\n\n" .

                "Thank you for your trust and support.",


            /*
            |--------------------------------------------------------------------------
            | REPEAT ORDER - 20 DAYS
            |--------------------------------------------------------------------------
            */

            'repeat_reminder' =>

            "Hi {$customerName},\n\n" .

                "It has been 20 days since you received your order.\n\n" .

                "We hope you are enjoying {$productName}.\n\n" .

                "Would you like to place another order?\n\n" .

                "Reply to this message and our team will be happy to assist you.",


            default => '',
        };
    }


    /**
     * Send payload to ByteSpeed.
     */
    protected function sendToByteSpeed(array $payload): array
    {
        $url = config(
            'services.bytespeed.webhook_url'
        );

        $secret = config(
            'services.bytespeed.secret'
        );


        /*
        |--------------------------------------------------------------------------
        | Configuration Validation
        |--------------------------------------------------------------------------
        */

        if (empty($url)) {

            throw new \RuntimeException(
                'ByteSpeed webhook URL is not configured.'
            );
        }

        if (empty($secret)) {

            throw new \RuntimeException(
                'ByteSpeed webhook secret is not configured.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | JSON
        |--------------------------------------------------------------------------
        */

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_THROW_ON_ERROR
        );


        /*
        |--------------------------------------------------------------------------
        | HMAC SHA256
        |--------------------------------------------------------------------------
        */

        $signature =
            'sha256=' .
            hash_hmac(
                'sha256',
                $jsonPayload,
                $secret
            );


        /*
        |--------------------------------------------------------------------------
        | HTTP Request
        |--------------------------------------------------------------------------
        */

        try {

            $response = Http::connectTimeout(5)
                ->timeout(15)

                ->withHeaders([

                    'Content-Type' =>
                    'application/json',

                    'Accept' =>
                    'application/json',

                    'X-Webhook-Event' =>
                    $payload['event'],

                    'X-Webhook-Event-ID' =>
                    $payload['event_id'],

                    'X-Webhook-Signature' =>
                    $signature,

                ])

                ->withBody(
                    $jsonPayload,
                    'application/json'
                )

                ->post($url);


            /*
            |--------------------------------------------------------------------------
            | Throw Exception on 4xx / 5xx
            |--------------------------------------------------------------------------
            */

            $response->throw();


            /*
            |--------------------------------------------------------------------------
            | Success Log
            |--------------------------------------------------------------------------
            */

            Log::info(
                'ByteSpeed webhook sent successfully.',
                [

                    'event' =>
                    $payload['event'],

                    'event_id' =>
                    $payload['event_id'],

                    'order_id' =>
                    $payload['order']['id'],

                    'status' =>
                    $payload['status'],

                    'response_status' =>
                    $response->status(),

                ]
            );


            return [

                'success' => true,

                'status' =>
                $response->status(),

                'response' =>
                $response->json(),

                'payload' =>
                $payload,

            ];
        } catch (\Throwable $e) {


            /*
            |--------------------------------------------------------------------------
            | Error Log
            |--------------------------------------------------------------------------
            */

            Log::error(
                'ByteSpeed webhook failed.',
                [

                    'event' =>
                    $payload['event'],

                    'event_id' =>
                    $payload['event_id'],

                    'order_id' =>
                    $payload['order']['id'],

                    'status' =>
                    $payload['status'],

                    'error' =>
                    $e->getMessage(),

                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Re-throw Exception
            |--------------------------------------------------------------------------
            |
            | Important for Queue retry.
            |
            */

            throw $e;
        }
    }
}
