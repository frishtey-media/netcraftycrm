<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\DelhiveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DelhiveryTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $shipmentId
    ) {}

    public function handle(
        DelhiveryService $service
    ): void {

        $shipment = Shipment::find($this->shipmentId);

        if (!$shipment) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | AWB required
        |--------------------------------------------------------------------------
        */

        if (!$shipment->awb) {

            $shipment->update([
                'status' => 'tracking_failed',
                'error_message' => 'AWB not available for tracking.',
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Call Delhivery Tracking API
        |--------------------------------------------------------------------------
        */

        $result = $service->track(
            $shipment->awb
        );

        if (!$result['success']) {

            $shipment->update([
                'status' => 'tracking_failed',
                'error_message' =>
                $result['message']
                    ?? 'Tracking API failed.',
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Save complete tracking response
        |--------------------------------------------------------------------------
        */

        $shipment->update([
            'tracking_response' =>
            $result['raw']
                ?? json_encode($result['data'] ?? []),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Parse Delhivery response
        |--------------------------------------------------------------------------
        */

        $data = $result['data'] ?? [];

        $tracking = $data['ShipmentData'][0]['Shipment']
            ?? null;

        if (!$tracking) {

            $shipment->update([
                'status' => 'tracking_failed',
                'error_message' => 'Invalid Delhivery tracking response.',
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Delhivery Status
        |--------------------------------------------------------------------------
        */

        $delhiveryStatus =
            $tracking['Status']['Status']
            ?? null;

        $instructions =
            $tracking['Status']['Instructions']
            ?? null;

        $statusCode =
            $tracking['Status']['StatusCode']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | Normalize status for CRM
        |--------------------------------------------------------------------------
        */

        $crmStatus = $this->mapStatus(
            $delhiveryStatus,
            $instructions,
            $statusCode
        );

        /*
        |--------------------------------------------------------------------------
        | Update Shipment
        |--------------------------------------------------------------------------
        */

        $shipment->update([

            'status' =>
            $crmStatus,

            'error_message' =>
            $instructions,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Order
        |--------------------------------------------------------------------------
        */

        $order = \App\Models\Order::find(
            $shipment->order_id
        );

        if ($order) {

            $order->update([
                'delivery_status' => $crmStatus,
            ]);
        }
        Log::info('DELHIVERY ORDER STATUS UPDATED', [
            'shipment_id' => $shipment->id,
            'shipment_order_id' => $shipment->order_id,
            'order_id' => $order->id,
            'crm_status' => $crmStatus,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delhivery → CRM Status Mapping
    |--------------------------------------------------------------------------
    */

    private function mapStatus(
        ?string $status,
        ?string $instructions,
        ?string $statusCode
    ): string {

        $status = strtolower(
            trim($status ?? '')
        );

        $instructions = strtolower(
            trim($instructions ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Delivered
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'delivered'
        ) {
            return 'delivered';
        }

        /*
        |--------------------------------------------------------------------------
        | RTO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($status, 'rto')
            ||
            str_contains($instructions, 'return')
        ) {
            return 'rto';
        }

        /*
        |--------------------------------------------------------------------------
        | Pickup
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($status, 'picked')
            ||
            str_contains($status, 'dispatched')
        ) {
            return 'picked_up';
        }

        /*
        |--------------------------------------------------------------------------
        | In Transit
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($status, 'transit')
            ||
            str_contains($status, 'in transit')
        ) {
            return 'in_transit';
        }

        /*
        |--------------------------------------------------------------------------
        | Out for Delivery
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($status, 'out for delivery')
        ) {
            return 'out_for_delivery';
        }

        /*
        |--------------------------------------------------------------------------
        | Manifested
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'manifested'
        ) {
            return 'manifested';
        }

        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */

        return $status ?: 'tracking_updated';
    }
}
