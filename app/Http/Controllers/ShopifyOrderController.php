<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\WhatsAppOrdersImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DelhiveryExcelImport;
use App\Services\DelhiveryService;
use App\Models\Shipment;

class ShopifyOrderController extends Controller
{
    public function whatsappExcelImport(Request $request)
    {
        $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'import_date' => 'required|date',
            'file'        => 'required|mimes:xls,xlsx',
        ]);

        $import = new WhatsAppOrdersImport(
            $request->client_id,
            $request->import_date
        );

        Excel::import($import, $request->file('file'));

        return back()->with([
            'success' => "Imported: {$import->imported}, Skipped: {$import->skipped}",
            'errors'  => $import->errors,
        ]);
    }

    public function delhiveryExcelImport(Request $request)
    {
        $request->validate([
            'client_id' =>
            'required|exists:clients,id',

            'import_date' =>
            'required|date',

            'file' =>
            'required|mimes:xls,xlsx',
        ]);

        /*
    |--------------------------------------------------------------------------
    | Import Excel
    |--------------------------------------------------------------------------
    */

        $import = new \App\Imports\DelhiveryExcelImport(
            (int) $request->client_id,
            $request->import_date
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        /*
    |--------------------------------------------------------------------------
    | Dispatch ONLY current Excel rows
    |--------------------------------------------------------------------------
    */

        foreach ($import->importedIds as $id) {

            \App\Jobs\DelhiveryBookingJob::dispatch(
                $id
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Import Summary
    |--------------------------------------------------------------------------
    */

        return back()->with([

            'delhivery_import_summary' => [

                'total_rows' =>
                $import->totalRows,

                'imported' =>
                $import->imported,

                'skipped' =>
                $import->skipped,

                'booking_queued' =>
                count(
                    $import->importedIds
                ),

            ],

            'delhivery_import_errors' =>
            $import->errors,

        ]);
    }
    public function requestDelhiveryPickup(
        Request $request,
        DelhiveryService $service
    ) {
        $request->validate([
            'pickup_date' => [
                'required',
                'date',
            ],

            'pickup_time' => [
                'required',
                'date_format:H:i',
            ],

            'expected_package_count' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);


        /*
    |--------------------------------------------------------------------------
    | Find shipments ready for pickup
    |--------------------------------------------------------------------------
    */

        $shipments = Shipment::where(
            'courier',
            'delhivery'
        )
            ->whereNotNull('awb')
            ->whereIn(
                'status',
                [
                    'booked',
                    'label_generated',
                ]
            )
            ->whereNull('picked_up_at')
            ->get();


        if ($shipments->isEmpty()) {

            return back()->with(
                'error',
                'No Delhivery shipments are ready for pickup.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Convert date
    |--------------------------------------------------------------------------
    */

        $pickupDate = date(
            'Y-m-d',
            strtotime($request->pickup_date)
        );


        /*
    |--------------------------------------------------------------------------
    | Create Pickup
    |--------------------------------------------------------------------------
    */

        $result = $service->createPickup(

            $pickupDate,

            $request->pickup_time,

            (int) $request->expected_package_count

        );


        /*
    |--------------------------------------------------------------------------
    | API FAILED
    |--------------------------------------------------------------------------
    */

        if (
            !($result['success'] ?? false)
        ) {

            return back()->with(
                'error',

                'Delhivery Pickup Request Failed: ' .
                    (
                        $result['message']
                        ?? $result['raw']
                        ?? 'Unknown error'
                    )
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Get API response
    |--------------------------------------------------------------------------
    */

        $data =
            $result['data']
            ?? [];


        /*
    |--------------------------------------------------------------------------
    | Find Pickup Request ID
    |--------------------------------------------------------------------------
    */

        $pickupRequestId =
            $data['pickup_id']
            ?? $data['pickup_request_id']
            ?? $data['request_id']
            ?? $data['prq_id']
            ?? null;


        /*
    |--------------------------------------------------------------------------
    | Save pickup information
    |--------------------------------------------------------------------------
    */

        foreach ($shipments as $shipment) {

            $shipment->update([

                'pickup_request_id' =>
                $pickupRequestId,

                'status' =>
                'pickup_requested',

                'error_message' =>
                null,

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

        return back()->with(

            'success',

            'Delhivery pickup requested successfully. ' .

                'Packages: ' .
                $request->expected_package_count .

                (
                    $pickupRequestId
                    ? ' | Pickup ID: ' . $pickupRequestId
                    : ''
                )

        );
    }
    public function delhiveryImportStatus(Request $request)
    {
        $clientId =
            $request->client_id;

        $date =
            $request->date
            ?? today()->toDateString();

        $query =
            \App\Models\DelhiveryImport::where(
                'client_id',
                $clientId
            )
            ->whereDate(
                'created_at',
                $date
            );

        return response()->json([

            'total' => (clone $query)->count(),

            'pending' => (clone $query)
                ->where(
                    'status',
                    'pending'
                )
                ->count(),

            'booking_success' => (clone $query)
                ->whereIn(
                    'status',
                    [
                        'order_created',
                        'label_generated',
                    ]
                )
                ->count(),

            'booking_failed' => (clone $query)
                ->whereIn(
                    'status',
                    [
                        'booking_failed',
                        'serviceability_failed',
                        'pincode_not_serviceable',
                    ]
                )
                ->count(),

            'label_generated' => (clone $query)
                ->where(
                    'status',
                    'label_generated'
                )
                ->count(),

        ]);
    }
    public function downloadDelhiveryLabel(
        \App\Models\Shipment $shipment
    ) {
        abort_unless(
            $shipment->courier === 'delhivery',
            404
        );

        abort_unless(
            $shipment->label_path,
            404
        );

        return \Storage::disk('public')
            ->download(
                $shipment->label_path,
                $shipment->awb . '.pdf'
            );
    }
    public function createDelhiveryPickup(
        Request $request,
        \App\Services\DelhiveryService $service
    ) {
        $request->validate([

            'pickup_date' =>
            'required|date',

            'pickup_time' =>
            'required',

            'package_count' =>
            'required|integer|min:1',

        ]);

        $result =
            $service->createPickup(

                $request->pickup_date,

                $request->pickup_time,

                $request->package_count

            );

        if (
            !$result['success']
        ) {

            return back()
                ->withErrors([
                    'pickup' =>
                    $result['raw']
                        ??
                        $result['message']
                        ??
                        'Pickup failed',
                ]);
        }

        return back()->with(
            'success',
            'Delhivery pickup request created.'
        );
    }
}
