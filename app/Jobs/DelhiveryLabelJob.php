<?php

namespace App\Jobs;

use App\Models\DelhiveryImport;
use App\Models\Shipment;
use App\Services\DelhiveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DelhiveryLabelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;


    public function __construct(
        public int $importId
    ) {}


    public function handle(
        DelhiveryService $service
    ): void {

        $item =
            DelhiveryImport::find(
                $this->importId
            );


        if (!$item) {
            return;
        }


        if (!$item->awb) {

            $item->update([
                'status' =>
                'label_failed',

                'error_message' =>
                'AWB not available for label generation.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Shipment
        |--------------------------------------------------------------------------
        */

        $shipment =
            Shipment::where(
                'awb',
                $item->awb
            )->first();


        if (!$shipment) {

            $item->update([
                'status' =>
                'label_failed',

                'error_message' =>
                'Shipment record not found.',
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Label
        |--------------------------------------------------------------------------
        */

        try {

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Use your existing generateLabel() method here.
            |
            */

            $result =
                $service->generateLabel(
                    $item->awb
                );


            if (
                !$result['success']
            ) {

                $item->update([

                    'status' =>
                    'label_failed',

                    'error_message' =>
                    $result['message']
                        ??
                        'Label generation failed.',

                ]);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Save label
            |--------------------------------------------------------------------------
            */

            $labelPath =
                $result['path']
                ??
                $result['label_path']
                ??
                null;


            $shipment->update([

                'label_path' =>
                $labelPath,

                'status' =>
                'label_generated',

            ]);


            $item->update([

                'status' =>
                'label_generated',

                'error_message' =>
                null,

            ]);


            Log::info(
                'DELHIVERY LABEL GENERATED',
                [
                    'import_id' =>
                    $item->id,

                    'awb' =>
                    $item->awb,

                    'label_path' =>
                    $labelPath,
                ]
            );
        } catch (\Throwable $e) {

            $item->update([

                'status' =>
                'label_failed',

                'error_message' =>
                $e->getMessage(),

            ]);


            Log::error(
                'DELHIVERY LABEL ERROR',
                [
                    'import_id' =>
                    $item->id,

                    'awb' =>
                    $item->awb,

                    'error' =>
                    $e->getMessage(),
                ]
            );
        }
    }
}
