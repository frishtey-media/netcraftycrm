<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\DelhiveryService;
use Illuminate\Console\Command;

class TrackDelhiveryShipments extends Command
{
    protected $signature =
    'delhivery:track';

    protected $description =
    'Track active Delhivery shipments';

    public function handle(
        DelhiveryService $service
    ) {

        Shipment::where(
            'courier',
            'delhivery'
        )
            ->whereNotNull('awb')
            ->whereNotIn(
                'status',
                [
                    'delivered',
                    'rto_delivered',
                ]
            )
            ->chunkById(
                50,
                function ($shipments)
                use ($service) {

                    foreach ($shipments as $shipment) {

                        $result =
                            $service->track(
                                $shipment->awb
                            );

                        if (
                            !$result['success']
                        ) {
                            continue;
                        }

                        $shipment->update([

                            'tracking_response' =>
                            json_encode(
                                $result['data']
                            ),

                        ]);

                        $this->updateStatus(
                            $shipment,
                            $result['data']
                        );
                    }
                }
            );

        return self::SUCCESS;
    }

    private function updateStatus(
        Shipment $shipment,
        ?array $data
    ): void {

        $status =
            data_get(
                $data,
                'ShipmentData.0.Shipment.Status.Status'
            );

        if (!$status) {
            return;
        }

        $statusLower =
            strtolower($status);

        $mapped = match (true) {

            str_contains(
                $statusLower,
                'delivered'
            ) =>
            'delivered',

            str_contains(
                $statusLower,
                'out for delivery'
            ) =>
            'out_for_delivery',

            str_contains(
                $statusLower,
                'in transit'
            ) =>
            'in_transit',

            str_contains(
                $statusLower,
                'manifest'
            ) =>
            'manifested',

            str_contains(
                $statusLower,
                'rto'
            ) =>
            'rto',

            default =>
            'in_transit',
        };

        $shipment->update([
            'status' => $mapped,
        ]);

        if ($shipment->order) {

            $shipment->order->update([
                'delivery_status' => $mapped,
            ]);
        }

        if ($mapped === 'delivered') {

            $shipment->update([
                'delivered_at' => now(),
            ]);
        }
    }
}
