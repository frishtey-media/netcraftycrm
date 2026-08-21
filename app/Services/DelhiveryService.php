<?php

namespace App\Services;

use App\Models\DelhiveryImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DelhiveryService
{
    protected string $baseUrl;

    protected string $token;


    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.delhivery.base_url'),
            '/'
        );

        $this->token =
            config('services.delhivery.api_token');
    }

    protected function request()
    {
        return Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Token ' . trim($this->token),

                'Accept' => 'application/json',

                'Content-Type' => 'application/json',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PINCODE SERVICEABILITY
    |--------------------------------------------------------------------------
    */

    public function checkPincode(
        string $pincode
    ): array {

        try {

            $response = $this->request()
                ->get(
                    $this->baseUrl .
                        '/c/api/pin-codes/json/',
                    [
                        'filter_codes' =>
                        $pincode,
                    ]
                );

            $data =
                $response->json();

            return [
                'success' =>
                $response->successful(),

                'data' =>
                $data,

                'raw' =>
                $response->body(),

            ];
        } catch (\Throwable $e) {

            Log::error(
                'Delhivery serviceability error',
                [
                    'pincode' =>
                    $pincode,

                    'error' =>
                    $e->getMessage(),
                ]
            );

            return [
                'success' => false,
                'data' => null,
                'raw' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK PAYMENT SERVICEABILITY
    |--------------------------------------------------------------------------
    */

    public function isPaymentServiceable(
        array $data,
        string $paymentMode
    ): bool {

        $postal =
            data_get(
                $data,
                'delivery_codes.0.postal_code'
            );

        if (!$postal) {
            return false;
        }

        $remarks =
            trim(
                (string) (
                    $postal['remarks'] ?? ''
                )
            );

        if ($remarks !== '') {
            return false;
        }

        if ($paymentMode === 'COD') {

            return strtoupper(
                (string) (
                    $postal['cash'] ??
                    $postal['cod'] ??
                    'N'
                )
            ) === 'Y';
        }

        return strtoupper(
            (string) (
                $postal['pre_paid'] ??
                'N'
            )
        ) === 'Y';
    }

    /*
    |--------------------------------------------------------------------------
    | BOOK SHIPMENT
    |--------------------------------------------------------------------------
    */

    public function book(DelhiveryImport $item): array
    {
        $payment = strtoupper(trim($item->payment_mode));

        if (!in_array($payment, ['COD', 'PREPAID'], true)) {
            return [
                'success' => false,
                'status' => 422,
                'data' => null,
                'message' => 'Only COD and PREPAID are supported.',
                'raw' => null,
            ];
        }

        $delhiveryPayment = $payment === 'COD'
            ? 'COD'
            : 'Pre-paid';

        $phone = $this->normalizePhone($item->customer_phone);

        $weight = (float) $item->weight;

        /*
    |--------------------------------------------------------------------------
    | PAYLOAD
    |--------------------------------------------------------------------------
    */

        $payload = [
            'pickup_location' => [
                'name' => config('services.delhivery.pickup_location'),
            ],

            'shipments' => [
                [
                    'client' => config('services.delhivery.client_name'),

                    'name' => $this->clean($item->customer_name),

                    'add' => $this->clean($item->shipping_address),

                    'city' => $this->clean($item->city),

                    'state' => $this->clean($item->state),

                    'country' => 'India',

                    'pin' => (string) $item->pincode,

                    'phone' => $phone,

                    'order' => substr($item->order_id, 0, 50),

                    'order_date' => optional($item->order_date)
                        ->format('Y-m-d H:i:s'),

                    'payment_mode' => $delhiveryPayment,

                    'products_desc' => $this->clean($item->product),

                    'quantity' => (int) ($item->quantity ?: 1),

                    'total_amount' => (float) $item->amount,

                    'cod_amount' => $payment === 'COD'
                        ? (float) $item->amount
                        : 0,

                    'weight' => $weight,

                    // Empty = Delhivery generates AWB
                    'waybill' => '',

                    'shipment_width' => '10',
                    'shipment_height' => '10',
                    'shipment_length' => '10',

                    'shipping_mode' => 'Surface',
                ],
            ],
        ];

        try {

            Log::info('DELHIVERY BOOKING REQUEST', [
                'order_id' => $item->order_id,
                'client' => config('services.delhivery.client_name'),
                'pickup_location' => config('services.delhivery.pickup_location'),
                'pincode' => $item->pincode,
                'payment_mode' => $delhiveryPayment,
                'payload' => $payload,
            ]);

            $response = $this->request()
                ->asForm()
                ->post(
                    $this->baseUrl . '/api/cmu/create.json',
                    [
                        'format' => 'json',
                        'data' => json_encode(
                            $payload,
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]
                );

            $data = $response->json();

            /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | HTTP 200 does NOT necessarily mean Delhivery booking succeeded.
        */

            $apiSuccess = ($data['success'] ?? false) === true;

            /*
         * Some successful Delhivery responses may not contain
         * top-level success=true, so check for packages as well.
         */

            $hasPackages = !empty($data['packages']);

            $success = $response->successful()
                && (
                    $apiSuccess
                    || $hasPackages
                );

            Log::info('DELHIVERY BOOKING RESPONSE', [
                'order_id' => $item->order_id,
                'http_status' => $response->status(),
                'api_success' => $apiSuccess,
                'has_packages' => $hasPackages,
                'response' => $data,
            ]);

            return [
                'success' => $success,

                'status' => $response->status(),

                'data' => $data,

                'raw' => $response->body(),

                'message' =>
                $data['rmk']
                    ?? $data['message']
                    ?? null,
            ];
        } catch (\Throwable $e) {

            Log::error('Delhivery booking exception', [
                'order_id' => $item->order_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'data' => null,
                'raw' => $e->getMessage(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EXTRACT AWB
    |--------------------------------------------------------------------------
    */

    public function extractAwb(
        array $data
    ): ?string {

        $possible = [

            data_get(
                $data,
                'packages.0.waybill'
            ),

            data_get(
                $data,
                'packages.0.Waybill'
            ),

            data_get(
                $data,
                'waybill'
            ),

            data_get(
                $data,
                'awb'
            ),

            data_get(
                $data,
                'AWB'
            ),

        ];

        foreach ($possible as $awb) {

            if (
                is_string($awb) &&
                trim($awb) !== ''
            ) {
                return trim($awb);
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | LABEL
    |--------------------------------------------------------------------------
    */

    public function generateLabel(string $awb): array
    {
        try {

            $response = $this->request()->get(
                $this->baseUrl . '/api/p/packing_slip',
                [
                    'wbns' => $awb,
                    'pdf' => 'True',
                ]
            );

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $response->body(),
                ];
            }

            $json = $response->json();

            /*
        |--------------------------------------------------------------------------
        | Delhivery JSON -> pdf_encoding
        |--------------------------------------------------------------------------
        */

            $pdfEncoding =
                data_get(
                    $json,
                    'packages.0.pdf_encoding'
                );

            if ($pdfEncoding) {

                $pdf = base64_decode(
                    $pdfEncoding,
                    true
                );

                if ($pdf === false) {
                    return [
                        'success' => false,
                        'message' => 'Invalid PDF encoding received from Delhivery.',
                    ];
                }

                /*
            |--------------------------------------------------------------------------
            | Verify PDF
            |--------------------------------------------------------------------------
            */

                if (!str_starts_with($pdf, '%PDF')) {
                    return [
                        'success' => false,
                        'message' => 'Decoded response is not a valid PDF.',
                    ];
                }

                $directory =
                    'delhivery-labels/' .
                    now()->format('Y/m/d');

                $filename = $awb . '.pdf';

                $path =
                    $directory . '/' . $filename;

                Storage::disk('public')->put(
                    $path,
                    $pdf
                );

                return [
                    'success' => true,
                    'path' => $path,
                    'url' =>
                    Storage::disk('public')->url($path),
                    'source' => 'pdf_encoding',
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | Direct PDF response fallback
        |--------------------------------------------------------------------------
        */

            $contentType = strtolower(
                (string) $response->header('Content-Type')
            );

            if (
                str_contains($contentType, 'pdf') ||
                str_starts_with($response->body(), '%PDF')
            ) {

                $directory =
                    'delhivery-labels/' .
                    now()->format('Y/m/d');

                $path =
                    $directory . '/' .
                    $awb . '.pdf';

                Storage::disk('public')->put(
                    $path,
                    $response->body()
                );

                return [
                    'success' => true,
                    'path' => $path,
                    'url' =>
                    Storage::disk('public')->url($path),
                    'source' => 'direct_pdf',
                ];
            }

            return [
                'success' => false,
                'message' => 'Delhivery did not return a PDF.',
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PICKUP
    |--------------------------------------------------------------------------
    */

    public function createPickup(
        string $date,
        string $time,
        int $count
    ): array {

        try {

            $response =
                $this->request()
                ->post(
                    $this->baseUrl .
                        '/fm/request/new/',
                    [

                        'pickup_time' =>
                        $time,

                        'pickup_date' =>
                        $date,

                        'pickup_location' =>
                        config(
                            'services.delhivery.pickup_location'
                        ),

                        'expected_package_count' =>
                        $count,

                    ]
                );


            $json = $response->json();


            return [

                'success' =>
                $response->successful(),

                'status' =>
                $response->status(),

                'data' =>
                $json,

                'raw' =>
                $response->body(),

            ];
        } catch (\Throwable $e) {

            return [

                'success' => false,

                'status' => 0,

                'data' => [],

                'message' =>
                $e->getMessage(),

            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRACK
    |--------------------------------------------------------------------------
    */

    public function track(
        string $awb
    ): array {

        try {

            $response =
                $this->request()
                ->get(
                    $this->baseUrl .
                        '/api/v1/packages/json/',
                    [
                        'waybill' =>
                        $awb,
                    ]
                );

            return [

                'success' =>
                $response->successful(),

                'data' =>
                $response->json(),

                'raw' =>
                $response->body(),

            ];
        } catch (\Throwable $e) {

            return [

                'success' => false,

                'message' =>
                $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function normalizePhone(
        ?string $phone
    ): string {

        $parts =
            preg_split(
                '/[\/,]+/',
                (string) $phone
            );

        $phone =
            trim(
                $parts[0] ?? ''
            );

        $phone =
            preg_replace(
                '/[^0-9+]/',
                '',
                $phone
            );

        return $phone;
    }

    private function clean(
        ?string $value
    ): string {

        $value =
            trim(
                (string) $value
            );

        /*
         * Delhivery documentation warns against
         * &, %, #, ; and backslash in payload.
         */

        $value =
            str_replace(
                [
                    '&',
                    '%',
                    '#',
                    ';',
                    '\\',
                ],
                ' ',
                $value
            );

        return preg_replace(
            '/\s+/',
            ' ',
            $value
        );
    }
}
