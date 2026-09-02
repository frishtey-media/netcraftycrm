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
            'client_id' => 'required|exists:clients,id',
            'import_date' => 'required|date',
            'file' => 'required|mimes:xls,xlsx',
            'package_type' => 'nullable|in:flyer,box',
            'shipping_mode' => 'nullable|in:surface,express',
        ]);

        $import = new \App\Imports\DelhiveryExcelImport(
            (int) $request->client_id,
            $request->import_date
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        // IMPORTANT: Import only. Booking starts after Confirm & Book.
        session([
            'delhivery_import_client_id' => (int) $request->client_id,
            'delhivery_review_client' => (int) $request->client_id,
            'delhivery_review_date' => $request->import_date,
            'delhivery_review_import_ids' => array_values(array_map('intval', $import->importedIds ?? [])),
            'delhivery_review_package_type' => $request->package_type ?: 'flyer',
            'delhivery_review_shipping_mode' => $request->shipping_mode ?: 'express',
        ]);

        return back()->with([
            'delhivery_import_summary' => [
                'total_rows' => $import->totalRows,
                'imported' => $import->imported,
                'skipped' => $import->skipped,
                'ready_for_review' => count($import->importedIds),
                'booking_queued' => 0,
                'booking_success' => 0,
                'booking_failed' => 0,
            ],

            'delhivery_import_errors' =>
            $import->errors,

            'open_delhivery_review' =>
            true,
        ]);
    }

    /**
     * Confirm selected Delhivery imports and queue booking.
     * Excel import itself never books automatically.
     */
    public function confirmDelhiveryImport(Request $request)
    {
        $request->validate([
            'import_ids' => ['required', 'array', 'min:1'],
            'import_ids.*' => ['integer', 'exists:delhivery_imports,id'],
            'package_type' => ['required', 'in:flyer,box'],
            'shipping_mode' => ['required', 'in:surface,express'],
        ]);

        $ids = collect($request->input('import_ids', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $items = \App\Models\DelhiveryImport::whereIn('id', $ids)
            ->whereNull('awb')
            ->whereIn('status', [
                'pending',
                'ready',
                'serviceability_checked',
            ])
            ->get();

        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'No eligible Delhivery shipments were selected.'
            );
        }

        $queued = 0;

        foreach ($items as $item) {
            $item->update([
                'status' => 'queued',
                'error_message' => null,
            ]);

            \App\Jobs\DelhiveryBookingJob::dispatch(
                (int) $item->id,
                $request->package_type,
                $request->shipping_mode
            );

            $queued++;
        }

        session([
            'delhivery_import_client_id' =>
            (int) $items->first()->client_id,
        ]);

        return back()->with([
            'success' =>
            $queued . ' Delhivery shipment(s) queued for booking.',

            'delhivery_import_summary' => [
                'total_rows' => $items->count(),
                'imported' => $items->count(),
                'skipped' => 0,
                'ready_for_review' => 0,
                'booking_queued' => $queued,
                'booking_success' => 0,
                'booking_failed' => 0,
            ],
        ]);
    }

    /**
     * JSON review/report for the selected client's latest import records.
     * Includes both parsed fields and complete saved Delhivery API responses.
     */

    private function numericOrNull($value): ?float
    {
        return is_numeric($value)
            ? (float) $value
            : null;
    }
    public function delhiveryImportPreview(
        Request $request,
        DelhiveryService $service
    ) {
        $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'date' => ['nullable', 'date'],
            'shipping_mode' => ['nullable', 'in:surface,express'],
            'import_ids' => ['nullable', 'string'],
        ]);

        $clientId = (int) $request->client_id;

        $date = $request->date
            ?: today()->toDateString();

        $shippingMode = strtolower(
            (string) ($request->shipping_mode ?: 'express')
        );

        /*
    |--------------------------------------------------------------------------
    | EXACT IMPORT IDS
    |--------------------------------------------------------------------------
    */

        $importIds = collect(
            explode(
                ',',
                (string) $request->input('import_ids', '')
            )
        )
            ->map(fn($id) => (int) trim($id))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        /*
    |--------------------------------------------------------------------------
    | LOAD IMPORTS
    |--------------------------------------------------------------------------
    */

        $itemsQuery = \App\Models\DelhiveryImport::where(
            'client_id',
            $clientId
        );

        if ($importIds->isNotEmpty()) {

            $itemsQuery->whereIn(
                'id',
                $importIds
            );
        } else {

            $itemsQuery->whereDate(
                'created_at',
                $date
            );
        }

        $items = $itemsQuery
            ->latest('id')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | PROCESS EACH ITEM
    |--------------------------------------------------------------------------
    */

        foreach ($items as $item) {

            /*
        |--------------------------------------------------------------------------
        | SERVICEABILITY
        |--------------------------------------------------------------------------
        */

            $serviceability =
                $this->decodeJson(
                    $item->serviceability_response
                );

            /*
        | Only call API if no saved serviceability response.
        */

            if (!$serviceability) {

                $result =
                    $service->checkPincode(
                        (string) $item->pincode
                    );

                $serviceability =
                    is_array($result['data'] ?? null)
                    ? $result['data']
                    : [];

                $item->serviceability_response =
                    json_encode(
                        $serviceability,
                        JSON_UNESCAPED_UNICODE
                    );

                if (!($result['success'] ?? false)) {

                    $item->status =
                        'serviceability_failed';

                    $item->error_message =
                        $this->serviceabilityError(
                            $result
                        );
                }

                $item->save();
            }

            /*
        |--------------------------------------------------------------------------
        | PAYMENT SERVICEABILITY
        |--------------------------------------------------------------------------
        */

            $payment = strtoupper(
                trim(
                    (string) $item->payment_mode
                )
            );

            $paymentServiceable =
                $service->isPaymentServiceable(
                    $serviceability,
                    $payment
                );

            if (
                !$paymentServiceable &&
                $serviceability
            ) {

                $item->status =
                    'pincode_not_serviceable';

                $item->error_message =
                    'Pincode is not serviceable for ' .
                    $payment;

                $item->save();

                /*
            | Do not calculate rate if payment
            | is not serviceable.
            */

                continue;
            }

            /*
        |--------------------------------------------------------------------------
        | EXISTING RATE RESPONSE
        |--------------------------------------------------------------------------
        */

            $shippingCostResponse =
                $this->decodeJson(
                    $item->shipping_cost_response
                );

            /*
        |--------------------------------------------------------------------------
        | SAVED MODE
        |--------------------------------------------------------------------------
        */

            $savedMode = strtolower(
                (string) data_get(
                    $shippingCostResponse,
                    '_crm.shipping_mode',
                    ''
                )
            );

            /*
        |--------------------------------------------------------------------------
        | EXTRACT EXISTING API COST
        |--------------------------------------------------------------------------
        |
        | This is important for your current response:
        |
        | {
        |     "0": {
        |         "total_amount": 81.03
        |     }
        | }
        |
        */

            $apiCost =
                $this->extractApiCost(
                    $shippingCostResponse
                );

            /*
        |--------------------------------------------------------------------------
        | STORED DB COST
        |--------------------------------------------------------------------------
        */

            $storedCost = null;

            if (
                $item->shipping_cost !== null &&
                $item->shipping_cost !== '' &&
                is_numeric($item->shipping_cost)
            ) {

                $storedCost =
                    (float) $item->shipping_cost;
            }

            /*
        |--------------------------------------------------------------------------
        | VALID SAVED RATE?
        |--------------------------------------------------------------------------
        |
        | Old failed records have:
        |
        | shipping_cost = 0
        | _crm.success = false
        |
        | So we MUST recalculate them.
        */

            $hasValidSavedRate =
                ($storedCost !== null && $storedCost > 0)
                ||
                $apiCost > 0;

            /*
        |--------------------------------------------------------------------------
        | NEED RATE
        |--------------------------------------------------------------------------
        */

            $needsRate =
                !$shippingCostResponse
                ||
                $savedMode !== $shippingMode
                ||
                !$hasValidSavedRate;

            /*
        |--------------------------------------------------------------------------
        | CALCULATE SHIPPING COST
        |--------------------------------------------------------------------------
        */

            if (
                $paymentServiceable &&
                $needsRate
            ) {

                $rate =
                    $service->calculateShippingCost(
                        $item,
                        $shippingMode
                    );

                /*
            |--------------------------------------------------------------------------
            | RAW DELHIVERY RESPONSE
            |--------------------------------------------------------------------------
            */

                $responseToSave =
                    is_array($rate['data'] ?? null)
                    ? $rate['data']
                    : [];

                /*
            |--------------------------------------------------------------------------
            | EXTRACT COST FROM ACTUAL RESPONSE
            |--------------------------------------------------------------------------
            |
            | Your response:
            |
            | 0.total_amount = 81.03
            |
            */

                $apiCost =
                    $this->extractApiCost(
                        $responseToSave
                    );

                /*
            |--------------------------------------------------------------------------
            | RATE COST
            |--------------------------------------------------------------------------
            */

                $returnedCost = null;

                if (
                    isset($rate['cost']) &&
                    is_numeric($rate['cost'])
                ) {

                    $returnedCost =
                        (float) $rate['cost'];
                }

                /*
            | Prefer actual API amount.
            */

                $finalCost =
                    $apiCost > 0
                    ? $apiCost
                    : ($returnedCost ?? 0);

                /*
            |--------------------------------------------------------------------------
            | EFFECTIVE SUCCESS
            |--------------------------------------------------------------------------
            |
            | Even if calculateShippingCost() old parser says
            | success=false, a valid numeric Delhivery amount
            | means rate calculation succeeded.
            */

                $effectiveSuccess =
                    ($rate['success'] ?? false)
                    ||
                    $finalCost > 0;

                /*
            |--------------------------------------------------------------------------
            | CRM META
            |--------------------------------------------------------------------------
            */

                $responseToSave['_crm'] = [

                    'shipping_mode' =>
                    $shippingMode,

                    'payment_mode' =>
                    $payment,

                    'origin_pincode' =>
                    config(
                        'services.delhivery.origin_pincode'
                    ),

                    'destination_pincode' =>
                    $item->pincode,

                    'weight_grams' =>
                    (int) round(
                        (float) $item->weight
                    ),

                    'calculated_at' =>
                    now()->toIso8601String(),

                    'success' =>
                    $effectiveSuccess,

                    'http_status' =>
                    $rate['status'] ?? null,

                    'message' =>
                    $effectiveSuccess
                        ? null
                        : (
                            $rate['message']
                            ?? 'Shipping cost could not be calculated.'
                        ),

                    'request' =>
                    $rate['request'] ?? [],

                    /*
                | Save parsed cost source
                */

                    'cost_source' =>
                    $apiCost > 0
                        ? '0.total_amount'
                        : 'rate.cost',

                    'calculated_cost' =>
                    $finalCost,
                ];

                /*
            |--------------------------------------------------------------------------
            | SAVE RESPONSE
            |--------------------------------------------------------------------------
            */

                $item->shipping_cost_response =
                    json_encode(
                        $responseToSave,
                        JSON_UNESCAPED_UNICODE
                    );

                /*
            |--------------------------------------------------------------------------
            | SAVE FINAL COST
            |--------------------------------------------------------------------------
            */

                $item->shipping_cost =
                    $finalCost;

                /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

                if (!$effectiveSuccess) {

                    $item->status =
                        'rate_failed';

                    $item->error_message =
                        $rate['message']
                        ?? 'Shipping cost could not be calculated.';
                } elseif (!in_array(
                    $item->status,
                    [
                        'booking_queued',
                        'booked',
                        'order_created',
                        'label_generated',
                        'completed',
                    ],
                    true
                )) {

                    $item->status =
                        'serviceability_checked';

                    $item->error_message =
                        null;
                }

                $item->save();
            }
        }

        /*
    |--------------------------------------------------------------------------
    | FINAL JSON PAYLOAD
    |--------------------------------------------------------------------------
    */

        $payload = $items->map(
            function ($item) {

                $serviceability =
                    $this->decodeJson(
                        $item->serviceability_response
                    );

                $booking =
                    $this->decodeJson(
                        $item->booking_response
                    );

                $shippingCostResponse =
                    $this->decodeJson(
                        $item->shipping_cost_response
                    );

                /*
            |--------------------------------------------------------------------------
            | COST
            |--------------------------------------------------------------------------
            */

                $dbCost = null;

                if (
                    $item->shipping_cost !== null &&
                    $item->shipping_cost !== '' &&
                    is_numeric($item->shipping_cost)
                ) {

                    $dbCost =
                        (float) $item->shipping_cost;
                }

                $apiCost =
                    $this->extractApiCost(
                        $shippingCostResponse
                    );

                /*
            | Final cost:
            | DB cost > API response cost
            */

                $finalCost =
                    ($dbCost !== null && $dbCost > 0)
                    ? $dbCost
                    : $apiCost;

                /*
            |--------------------------------------------------------------------------
            | RATE DETAILS
            |--------------------------------------------------------------------------
            */

                $rateRow =
                    data_get(
                        $shippingCostResponse,
                        '0',
                        []
                    );

                $grossAmount =
                    is_numeric(
                        data_get(
                            $rateRow,
                            'gross_amount'
                        )
                    )
                    ? (float) data_get(
                        $rateRow,
                        'gross_amount'
                    )
                    : null;

                $totalAmount =
                    is_numeric(
                        data_get(
                            $rateRow,
                            'total_amount'
                        )
                    )
                    ? (float) data_get(
                        $rateRow,
                        'total_amount'
                    )
                    : $finalCost;

                $taxAmount =
                    (
                        $totalAmount !== null &&
                        $grossAmount !== null
                    )
                    ? round(
                        $totalAmount -
                            $grossAmount,
                        2
                    )
                    : null;

                return [

                    'id' =>
                    $item->id,

                    'order_id' =>
                    $item->order_id,

                    'customer_name' =>
                    $item->customer_name,

                    'customer_phone' =>
                    $item->customer_phone,

                    'shipping_address' =>
                    $item->shipping_address,

                    'city' =>
                    $item->city,

                    'state' =>
                    $item->state,

                    'pincode' =>
                    $item->pincode,

                    'payment_mode' =>
                    $item->payment_mode,

                    'amount' =>
                    $item->amount,

                    'product' =>
                    $item->product,

                    'quantity' =>
                    $item->quantity,

                    'weight' =>
                    $item->weight,

                    'awb' =>
                    $item->awb,

                    'status' =>
                    $item->status ?: 'pending',

                    'error_message' =>
                    $item->error_message,

                    /*
                |--------------------------------------------------------------------------
                | FINAL COST
                |--------------------------------------------------------------------------
                */

                    'shipping_cost' =>
                    $finalCost,

                    /*
                |--------------------------------------------------------------------------
                | COST BREAKDOWN
                |--------------------------------------------------------------------------
                */

                    'rate_details' => [

                        'gross_amount' =>
                        $grossAmount,

                        'total_amount' =>
                        $totalAmount,

                        'tax_amount' =>
                        $taxAmount,

                        'charge_DL' =>
                        $this->numericOrNull(
                            data_get(
                                $rateRow,
                                'charge_DL'
                            )
                        ),

                        'charge_COD' =>
                        $this->numericOrNull(
                            data_get(
                                $rateRow,
                                'charge_COD'
                            )
                        ),

                        'charge_DPH' =>
                        $this->numericOrNull(
                            data_get(
                                $rateRow,
                                'charge_DPH'
                            )
                        ),

                        'charge_PEAK' =>
                        $this->numericOrNull(
                            data_get(
                                $rateRow,
                                'charge_PEAK'
                            )
                        ),

                        'charged_weight' =>
                        $this->numericOrNull(
                            data_get(
                                $rateRow,
                                'charged_weight'
                            )
                        ),
                    ],

                    /*
                |--------------------------------------------------------------------------
                | RAW API
                |--------------------------------------------------------------------------
                */

                    'shipping_cost_response' =>
                    $shippingCostResponse,

                    'shipping_mode' =>
                    data_get(
                        $shippingCostResponse,
                        '_crm.shipping_mode'
                    ),

                    /*
                |--------------------------------------------------------------------------
                | SERVICEABILITY
                |--------------------------------------------------------------------------
                */

                    'serviceability_status' =>
                    $this->serviceabilityStatus(
                        $serviceability,
                        (string) $item->payment_mode
                    ),

                    'serviceability_response' =>
                    $serviceability,

                    /*
                |--------------------------------------------------------------------------
                | BOOKING
                |--------------------------------------------------------------------------
                */

                    'booking_response' =>
                    $booking,

                    /*
                |--------------------------------------------------------------------------
                | API MESSAGE
                |--------------------------------------------------------------------------
                */

                    'api_message' =>
                    data_get(
                        $booking,
                        'message'
                    )
                        ??
                        data_get(
                            $booking,
                            'rmk'
                        )
                        ??
                        data_get(
                            $shippingCostResponse,
                            '_crm.message'
                        ),

                    'booking_error' =>
                    data_get(
                        $booking,
                        'error'
                    ),

                    'serviceability_error' =>
                    data_get(
                        $serviceability,
                        'error'
                    )
                        ??
                        data_get(
                            $serviceability,
                            'message'
                        ),
                ];
            }
        )->values();

        return response()->json([
            'success' =>
            true,

            'date' =>
            $date,

            'shipping_mode' =>
            $shippingMode,

            'items' =>
            $payload,
        ]);
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!$value) {
            return [];
        }

        $decoded = json_decode(
            $value,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private function serviceabilityError(array $result): string
    {
        $data = $result['data'] ?? [];

        if (is_array($data)) {
            return (string) (
                data_get($data, 'error')
                ?? data_get($data, 'message')
                ?? data_get($data, 'rmk')
                ?? 'Delhivery serviceability API failed.'
            );
        }

        return (string) (
            $result['raw']
            ?? 'Delhivery serviceability API failed.'
        );
    }

    private function serviceabilityStatus(
        array $response,
        string $paymentMode
    ): string {

        if (!$response) {
            return 'pending';
        }

        /*
    |--------------------------------------------------------------------------
    | FIND POSTAL DATA
    |--------------------------------------------------------------------------
    */

        $postal =
            data_get(
                $response,
                'delivery_codes.0.postal_code',
                []
            );

        /*
    | Some responses may be wrapped in data.
    */

        if (!$postal) {

            $postal =
                data_get(
                    $response,
                    'data.delivery_codes.0.postal_code',
                    []
                );
        }

        if (!$postal) {
            return 'failed';
        }

        /*
    |--------------------------------------------------------------------------
    | REMARKS
    |--------------------------------------------------------------------------
    */

        $remarks =
            trim(
                (string) (
                    $postal['remarks'] ?? ''
                )
            );

        if ($remarks !== '') {
            return 'not serviceable';
        }

        /*
    |--------------------------------------------------------------------------
    | COD
    |--------------------------------------------------------------------------
    */

        if (
            strtoupper(
                trim($paymentMode)
            ) === 'COD'
        ) {

            return strtoupper(
                (string) (
                    $postal['cash']
                    ??
                    $postal['cod']
                    ??
                    'N'
                )
            ) === 'Y'
                ? 'serviceable'
                : 'not serviceable';
        }

        /*
    |--------------------------------------------------------------------------
    | PREPAID
    |--------------------------------------------------------------------------
    */

        return strtoupper(
            (string) (
                $postal['pre_paid']
                ?? 'N'
            )
        ) === 'Y'
            ? 'serviceable'
            : 'not serviceable';
    }
    private function findApiAmount(array $data): float
    {
        /*
    | Prefer total_amount
    */

        if (
            isset($data['total_amount']) &&
            is_numeric($data['total_amount'])
        ) {

            return round(
                (float) $data['total_amount'],
                2
            );
        }

        /*
    | Fallback gross_amount
    */

        if (
            isset($data['gross_amount']) &&
            is_numeric($data['gross_amount'])
        ) {

            return round(
                (float) $data['gross_amount'],
                2
            );
        }

        foreach ($data as $value) {

            if (is_array($value)) {

                $amount =
                    $this->findApiAmount(
                        $value
                    );

                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        return 0.0;
    }
    private function extractApiCost(array $response): float
    {
        /*
    |--------------------------------------------------------------------------
    | ACTUAL DELHIVERY RATE RESPONSE
    |--------------------------------------------------------------------------
    |
    | {
    |     "0": {
    |         "gross_amount": 68.67,
    |         "total_amount": 81.03
    |     }
    | }
    |
    */

        $paths = [

            /*
        | Actual response
        */

            '0.total_amount',
            '0.gross_amount',

            /*
        | Nested response
        */

            'data.0.total_amount',
            'data.0.gross_amount',

            /*
        | Other possible structures
        */

            'data.total_amount',
            'data.gross_amount',

            'data.cost',
            'data.shipping_cost',
            'data.total_cost',

            'data.packages.0.cost',
            'data.packages.0.shipping_cost',
            'data.packages.0.total_cost',

            'cost',
            'shipping_cost',
            'total_cost',

            'packages.0.cost',
            'packages.0.shipping_cost',
            'packages.0.total_cost',
        ];

        foreach ($paths as $path) {

            $value =
                data_get(
                    $response,
                    $path
                );

            if (
                $value !== null &&
                $value !== '' &&
                is_numeric($value)
            ) {

                return round(
                    (float) $value,
                    2
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | RECURSIVE FALLBACK
    |--------------------------------------------------------------------------
    */

        return $this->findApiAmount(
            $response
        );
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

    private function extractDelhiveryCost(array $response): float
    {
        /*
    |--------------------------------------------------------------------------
    | Delhivery Rate Response
    |--------------------------------------------------------------------------
    |
    | Actual response example:
    |
    | {
    |     "0": {
    |         "gross_amount": 68.67,
    |         "total_amount": 81.03,
    |         "charge_DL": 45,
    |         "charge_COD": 20
    |     },
    |     "_crm": {...}
    | }
    |
    */

        $possiblePaths = [

            // -------------------------------------------------
            // ACTUAL DELHIVERY RATE RESPONSE
            // -------------------------------------------------

            '0.total_amount',
            '0.gross_amount',

            // Sometimes numeric index may be inside data
            'data.0.total_amount',
            'data.0.gross_amount',

            // -------------------------------------------------
            // Other possible response structures
            // -------------------------------------------------

            'data.cost',
            'data.shipping_cost',
            'data.total_cost',

            'data.packages.0.cost',
            'data.packages.0.shipping_cost',
            'data.packages.0.total_cost',

            'cost',
            'shipping_cost',
            'total_cost',

            'packages.0.cost',
            'packages.0.shipping_cost',
            'packages.0.total_cost',

        ];

        foreach ($possiblePaths as $path) {

            $value = data_get($response, $path);

            if (
                $value !== null &&
                $value !== '' &&
                is_numeric($value)
            ) {
                return round((float) $value, 2);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Recursive fallback
    |--------------------------------------------------------------------------
    |
    | If Delhivery changes response nesting in future,
    | search recursively for total_amount / gross_amount.
    |
    */

        $recursiveAmount = $this->findDelhiveryAmount($response);

        if ($recursiveAmount !== null) {
            return round($recursiveAmount, 2);
        }

        return 0.0;
    }
    private function findDelhiveryAmount(array $data): ?float
    {
        /*
    | Prefer total_amount because it is the final billed amount.
    */

        if (
            isset($data['total_amount']) &&
            is_numeric($data['total_amount'])
        ) {
            return (float) $data['total_amount'];
        }

        /*
    | gross_amount is fallback.
    */

        if (
            isset($data['gross_amount']) &&
            is_numeric($data['gross_amount'])
        ) {
            return (float) $data['gross_amount'];
        }

        foreach ($data as $value) {

            if (is_array($value)) {

                $amount = $this->findDelhiveryAmount($value);

                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        return null;
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
