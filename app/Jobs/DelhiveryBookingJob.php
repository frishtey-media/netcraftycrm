<?php

namespace App\Jobs;

use App\Models\DelhiveryImport;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\DelhiveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DelhiveryBookingJob implements ShouldQueue
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

        $item = DelhiveryImport::find(
            $this->importId
        );

        if (!$item) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Already Completed
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $item->status,
                [
                    'order_created',
                    'label_generated',
                    'completed',
                ],
                true
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Already Booked
        |--------------------------------------------------------------------------
        */

        if ($item->awb) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Serviceability
        |--------------------------------------------------------------------------
        */

        $serviceability =
            $service->checkPincode(
                $item->pincode
            );

        $item->update([

            'serviceability_response' =>
            json_encode(
                $serviceability['data']
                    ?? null
            ),

        ]);

        /*
        |--------------------------------------------------------------------------
        | Serviceability Failed
        |--------------------------------------------------------------------------
        */

        if (
            !($serviceability['success'] ?? false)
        ) {

            $item->update([

                'status' =>
                'serviceability_failed',

                'error_message' =>
                $this->extractServiceabilityError(
                    $serviceability
                ),

            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | COD / PREPAID Serviceability
        |--------------------------------------------------------------------------
        */

        $payment =
            strtoupper(
                trim(
                    $item->payment_mode
                )
            );

        if (
            !$service->isPaymentServiceable(
                $serviceability['data']
                    ?? [],
                $payment
            )
        ) {

            $item->update([

                'status' =>
                'pincode_not_serviceable',

                'error_message' =>
                'Pincode is not serviceable for '
                    . $payment,

            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | BOOK SHIPMENT
        |--------------------------------------------------------------------------
        */

        $result =
            $service->book(
                $item
            );

        /*
        |--------------------------------------------------------------------------
        | Save Complete API Response
        |--------------------------------------------------------------------------
        */

        $item->update([

            'booking_response' =>
            json_encode(
                $result['data']
                    ?? null
            ),

        ]);

        /*
        |--------------------------------------------------------------------------
        | Booking Failed
        |--------------------------------------------------------------------------
        */

        if (
            !($result['success'] ?? false)
        ) {

            $item->update([

                'status' =>
                'booking_failed',

                'error_message' =>
                $this->extractBookingError(
                    $result
                ),

            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Extract AWB
        |--------------------------------------------------------------------------
        */

        $awb =
            $service->extractAwb(
                $result['data']
                    ?? []
            );

        /*
        |--------------------------------------------------------------------------
        | AWB Not Found
        |--------------------------------------------------------------------------
        */

        if (!$awb) {

            $item->update([

                'status' =>
                'booking_failed',

                'error_message' =>
                'Booking response received but AWB was not found.',

            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Create Order + Shipment
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $item,
                $awb,
                $result
            ) {

                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Order
                |--------------------------------------------------------------------------
                */

                $order =
                    Order::where(
                        'order_id',
                        $item->order_id
                    )
                    ->where(
                        'client_id',
                        $item->client_id
                    )
                    ->first();

                if (!$order) {

                    $order =
                        Order::create([

                            'order_id' =>
                            $item->order_id,

                            'client_id' =>
                            $item->client_id,

                            'shopify_order_id' =>
                            $item->shopify_order_id,

                            'date' =>
                            $item->order_date,

                            'payment_mode' =>
                            $item->payment_mode,

                            'amount' =>
                            $item->amount,

                            'customer_name' =>
                            $item->customer_name,

                            'father_name' =>
                            $item->father_name,

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

                            'product' =>
                            $item->product,

                            'quantity' =>
                            $item->quantity,

                            'weight' =>
                            $item->weight,

                            'age' =>
                            $item->age,

                            'assigned_staff' =>
                            $item->assigned_staff,

                            'delivery' =>
                            'delhivery',

                            'delivery_status' =>
                            'booked',

                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Shipment
                |--------------------------------------------------------------------------
                */

                $shipment =
                    Shipment::where(
                        'order_id',
                        $order->id
                    )
                    ->where(
                        'courier',
                        'delhivery'
                    )
                    ->first();

                if (!$shipment) {

                    Shipment::create([

                        'order_id' =>
                        $order->id,

                        'courier' =>
                        'delhivery',

                        'awb' =>
                        $awb,

                        'status' =>
                        'booked',

                        'booking_response' =>
                        json_encode(
                            $result['data']
                                ?? null
                        ),

                        'booked_at' =>
                        now(),

                    ]);
                } else {

                    $shipment->update([

                        'awb' =>
                        $awb,

                        'status' =>
                        'booked',

                        'booking_response' =>
                        json_encode(
                            $result['data']
                                ?? null
                        ),

                        'booked_at' =>
                        now(),

                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Update Import
                |--------------------------------------------------------------------------
                */

                $item->update([

                    'awb' =>
                    $awb,

                    'status' =>
                    'order_created',

                    'error_message' =>
                    null,

                ]);

                /*
                |--------------------------------------------------------------------------
                | Generate Label
                |--------------------------------------------------------------------------
                */

                \App\Jobs\DelhiveryLabelJob::dispatch(
                    $order->id
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Error Extractor
    |--------------------------------------------------------------------------
    */

    private function extractBookingError(
        array $result
    ): string {

        $data =
            $result['data']
            ?? [];

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Main API Error
        |--------------------------------------------------------------------------
        */

        if (
            !empty($data['rmk'])
        ) {

            $errors[] =
                $data['rmk'];
        }

        if (
            !empty($data['message'])
        ) {

            $errors[] =
                $data['message'];
        }

        if (
            !empty($data['error'])
        ) {

            if (
                is_string(
                    $data['error']
                )
            ) {

                $errors[] =
                    $data['error'];
            } elseif (
                is_array(
                    $data['error']
                )
            ) {

                $errors[] =
                    json_encode(
                        $data['error']
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Package Errors
        |--------------------------------------------------------------------------
        */

        foreach (
            ($data['packages'] ?? [])
            as $package
        ) {

            if (
                !empty($package['remarks'])
            ) {

                foreach (
                    (array)
                    $package['remarks']
                    as $remark
                ) {

                    if (
                        is_string($remark)
                        &&
                        trim($remark) !== ''
                    ) {

                        $errors[] =
                            trim($remark);
                    }
                }
            }

            if (
                !empty($package['error'])
            ) {

                $errors[] =
                    is_string(
                        $package['error']
                    )
                    ? $package['error']
                    : json_encode(
                        $package['error']
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Service Message
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors)
            &&
            !empty($result['message'])
        ) {

            $errors[] =
                $result['message'];
        }

        /*
        |--------------------------------------------------------------------------
        | Raw Response
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors)
            &&
            !empty($result['raw'])
        ) {

            $errors[] =
                is_string(
                    $result['raw']
                )
                ? $result['raw']
                : json_encode(
                    $result['raw']
                );
        }

        if (empty($errors)) {

            $errors[] =
                'Unknown Delhivery booking error';
        }

        return implode(
            ' | ',
            array_unique($errors)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Serviceability Error Extractor
    |--------------------------------------------------------------------------
    */

    private function extractServiceabilityError(
        array $result
    ): string {

        if (
            !empty($result['message'])
        ) {

            return is_string(
                $result['message']
            )
                ? $result['message']
                : json_encode(
                    $result['message']
                );
        }

        if (
            !empty($result['raw'])
        ) {

            return is_string(
                $result['raw']
            )
                ? $result['raw']
                : json_encode(
                    $result['raw']
                );
        }

        return 'Pincode serviceability check failed.';
    }
}
