<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\DelhiveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DelhiveryPickupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(
        DelhiveryService $service
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Pick all shipments ready for pickup
        |--------------------------------------------------------------------------
        */

        $shipments = Shipment::where(
            'courier',
            'delhivery'
        )
            ->whereNotNull('awb')
            ->whereNotNull('label_path')
            ->whereNull('pickup_request_id')
            ->whereIn('status', [
                'label_generated',
                'booked',
            ])
            ->get();

        if ($shipments->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Pickup configuration
        |--------------------------------------------------------------------------
        */

        $date = now()
            ->format('Y-m-d');

        $time = config(
            'services.delhivery.pickup_time',
            '15:00:00'
        );

        $count = $shipments->count();

        /*
        |--------------------------------------------------------------------------
        | Create ONE pickup request
        |--------------------------------------------------------------------------
        */

        $result =
            $service->createPickup(
                $date,
                $time,
                $count
            );

        /*
        |--------------------------------------------------------------------------
        | Failed
        |--------------------------------------------------------------------------
        */

        if (!$result['success']) {

            foreach ($shipments as $shipment) {

                $shipment->update([

                    'pickup_status' =>
                    'failed',

                    'status' =>
                    'pickup_failed',

                    'error_message' =>
                    $result['message']
                        ?? $result['raw']
                        ?? 'Pickup request failed.',

                    'pickup_response' =>
                    $result['data']
                        ?? null,

                ]);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Pickup Request ID
        |--------------------------------------------------------------------------
        */

        $data =
            $result['data']
            ?? [];

        /*
        | Different Delhivery responses can expose
        | the pickup identifier differently.
        */

        $pickupRequestId =
            data_get(
                $data,
                'pickup_request_id'
            )
            ??
            data_get(
                $data,
                'pickup_id'
            )
            ??
            data_get(
                $data,
                'pr_id'
            )
            ??
            data_get(
                $data,
                'request_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Save Pickup against all shipments
        |--------------------------------------------------------------------------
        */

        foreach ($shipments as $shipment) {

            $shipment->update([

                'pickup_request_id' =>
                $pickupRequestId,

                'pickup_status' =>
                'scheduled',

                'pickup_date' =>
                $date,

                'pickup_time' =>
                $time,

                'pickup_response' =>
                $data,

                'pickup_requested_at' =>
                now(),

                'status' =>
                'pickup_scheduled',

                'error_message' =>
                null,

            ]);
        }
    }
}
