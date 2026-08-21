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

class DelhiveryLabelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $orderId
    ) {}

    public function handle(
        DelhiveryService $service
    ): void {

        $order = Order::find($this->orderId);

        if (!$order) {
            return;
        }

        $shipment = Shipment::where(
            'order_id',
            $order->id
        )
            ->where(
                'courier',
                'delhivery'
            )
            ->first();

        if (!$shipment) {
            return;
        }

        if ($shipment->label_path) {
            return;
        }

        if (!$shipment->awb) {

            $shipment->update([
                'status' => 'label_failed',
                'error_message' =>
                'AWB not available.',
            ]);

            return;
        }

        $result =
            $service->generateLabel(
                $shipment->awb
            );

        if (!$result['success']) {

            $shipment->update([

                'status' =>
                'label_failed',

                'error_message' =>
                $result['message']
                    ?? 'Label generation failed.',

            ]);

            return;
        }

        $shipment->update([

            'label_path' =>
            $result['path'],

            'label_url' =>
            $result['url'],

            'status' =>
            'label_generated',

            'label_generated_at' =>
            now(),

            'error_message' =>
            null,

        ]);

        $order->update([

            'delivery_status' =>
            'label_generated',

        ]);
    }
}
