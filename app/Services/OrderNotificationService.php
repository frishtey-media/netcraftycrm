<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    protected $byteSpeed;
    protected $aiSensy;

    public function __construct(
        ByteSpeedService $byteSpeed,
        AiSensyService $aiSensy
    ) {
        $this->byteSpeed = $byteSpeed;
        $this->aiSensy = $aiSensy;
    }

    public function send(Order $order, string $status): bool
    {
        /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

        $clientId = (int) $order->client_id;

        /*
        |--------------------------------------------------------------------------
        | STATUS NORMALIZATION
        |--------------------------------------------------------------------------
        */

        $statusKey = $this->normalizeStatus($status);

        if (!$statusKey) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $alreadySent = DB::table('order_notification_logs')
            ->where('order_id', $order->id)
            ->where('client_id', $clientId)
            ->where('status', $statusKey)
            ->exists();

        if ($alreadySent) {

            Log::info('NOTIFICATION ALREADY SENT', [
                'order_id' => $order->id,
                'client_id' => $clientId,
                'status'   => $statusKey,
            ]);

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | COMMON DATA
        |--------------------------------------------------------------------------
        */

        $trackingUrl = $this->trackingUrl($order);

        $payload = [
            'client_id'      => $clientId,
            'status'         => $statusKey,
            'order_id'       => $order->order_id,
            'tracking_id'    => $order->barcode,
            'customer_name'  => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'product'        => $order->product,
            'quantity'       => $order->quantity,
            'payment_type'   => $order->payment_mode,
            'amount'         => $order->amount,

            'address' => [
                'address' => $order->shipping_address,
                'city'    => $order->city,
                'state'   => $order->state,
                'pincode' => $order->pincode,
            ],

            'tracking_url' => $trackingUrl,

            'delivery_status' => $order->delivery_status,

            'delivery_date' => $order->delivery_date,

            'delivery_remark' => $order->delivery_remark,

            'event_time' => now()->toDateTimeString(),
        ];

        /*
        |--------------------------------------------------------------------------
        | CLIENT 5 = BYTESPEED
        |--------------------------------------------------------------------------
        */

        if (
            $clientId === 5 &&
            config('services.bytespeed.enabled')
        ) {

            $success = $this->byteSpeed->send($payload);
        }

        /*
        |--------------------------------------------------------------------------
        | CLIENT 2 = AISENSY
        |--------------------------------------------------------------------------
        */ elseif (
            $clientId === 2 &&
            config('services.aisensy.enabled')
        ) {

            $success = $this->sendAiSensy(
                $order,
                $statusKey,
                $trackingUrl
            );
        } else {

            Log::info('NO NOTIFICATION PROVIDER', [
                'client_id' => $clientId,
                'order_id'  => $order->order_id,
            ]);

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE LOG ONLY AFTER SUCCESS
        |--------------------------------------------------------------------------
        */

        if ($success) {

            DB::table('order_notification_logs')->insert([
                'order_id'     => $order->id,
                'client_id'    => $clientId,
                'status'       => $statusKey,
                'provider'     => $clientId === 5
                    ? 'bytespeed'
                    : 'aisensy',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            Log::info('ORDER NOTIFICATION SENT', [
                'order_id' => $order->order_id,
                'client_id' => $clientId,
                'status'   => $statusKey,
            ]);

            return true;
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | AISENSY
    |--------------------------------------------------------------------------
    */

    protected function sendAiSensy(
        Order $order,
        string $status,
        string $trackingUrl
    ): bool {

        $campaigns = config('services.aisensy.campaigns');

        $campaign = $campaigns[$status] ?? null;

        if (!$campaign) {

            Log::warning('AISENSY CAMPAIGN NOT FOUND', [
                'status' => $status,
            ]);

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE PARAMETERS
        |--------------------------------------------------------------------------
        |
        | Keep this SAME ORDER as the approved AiSensy template.
        |
        */

        $params = [
            $order->customer_name ?? 'Customer',
            $order->order_id ?? '',
            $order->product ?? '',
            $order->barcode ?? '',
            $trackingUrl,
            $order->payment_mode ?? '',
        ];

        return $this->aiSensy->send(
            $campaign,
            $order->customer_phone,
            $order->customer_name,
            $params
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    protected function normalizeStatus(string $status): ?string
    {
        $status = strtolower(trim($status));

        if (
            $status === 'booked' ||
            $status === 'confirmed' ||
            $status === 'label_printed'
        ) {
            return 'booked';
        }

        if (
            $status === 'shipped' ||
            $status === 'dispatch'
        ) {
            return 'shipped';
        }

        if (
            str_contains($status, 'in transit') ||
            str_contains($status, 'intransit')
        ) {
            return 'intransit';
        }

        if (
            str_contains($status, 'out for delivery') ||
            str_contains($status, 'ofd')
        ) {
            return 'ofd';
        }

        if (
            str_contains($status, 'hold')
        ) {
            return 'hold';
        }

        if (
            $status === 'delivered'
        ) {
            return 'delivered';
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | TRACKING URL
    |--------------------------------------------------------------------------
    */

    protected function trackingUrl(Order $order): ?string
    {
        if (!$order->barcode) {
            return null;
        }

        return 'https://myspeedpost.com/speed-post-tracking?n='
            . urlencode($order->barcode)
            . '&sync=true';
    }
}
