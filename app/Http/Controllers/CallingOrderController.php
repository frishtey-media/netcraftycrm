<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\callingorder;
use App\Models\Client;
use App\Models\ClientProduct;

class CallingOrderController extends Controller
{


    private function callingStaff()
    {
        return Auth::guard('calling_user')->user();
    }


    private function normalizePhone($phone)
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($phone) === 12 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        return $phone;
    }


    /*
    |--------------------------------------------------------------------------
    | Manual WhatsApp Order Page
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $staff = Auth::guard('calling_user')->user();

        if (!$staff) {
            abort(403, 'Calling staff login required.');
        }

        $clients = Client::orderBy('client_name')->get();

        return view('calling.manual_order', compact(
            'clients',
            'staff'
        ));
    }

    public function customerHistory(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | NORMALIZE PHONE
    |--------------------------------------------------------------------------
    */

        $phone = $this->normalizePhone($request->phone);

        if (!$phone || strlen($phone) < 7) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number.',
                'orders'  => [],
            ], 422);
        }


        /*
    |--------------------------------------------------------------------------
    | NORMALIZE CALLINGORDER PHONE
    |--------------------------------------------------------------------------
    */

        $cleanCallingPhone = "
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                callingorder.customer_phone,
                                ' ',
                                ''
                            ),
                            '+',
                            ''
                        ),
                        '-',
                        ''
                    ),
                    '(',
                    ''
                ),
                ')',
                ''
            ),
            '.',
            ''
        )
    ";


        /*
    |--------------------------------------------------------------------------
    | NORMALIZE ORDERS PHONE
    |--------------------------------------------------------------------------
    */

        $cleanOrdersPhone = "
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                orders.customer_phone,
                                ' ',
                                ''
                            ),
                            '+',
                            ''
                        ),
                        '-',
                        ''
                    ),
                    '(',
                    ''
                ),
                ')',
                ''
            ),
            '.',
            ''
        )
    ";


        /*
    |--------------------------------------------------------------------------
    | GET CALLING ORDER HISTORY
    |--------------------------------------------------------------------------
    |
    | callingorder.status:
    |
    | pending
    | verified
    | not_reachable
    | same_order
    | cancel
    |
    */

        $callingOrders = DB::table('callingorder')

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'callingorder.client_id'
            )

            ->leftJoin(
                'calling_users',
                'calling_users.id',
                '=',
                'callingorder.assigned_to'
            )

            ->where(function ($query) use (
                $cleanCallingPhone,
                $phone
            ) {

                /*
            | Exact normalized phone
            */

                $query->whereRaw(
                    "{$cleanCallingPhone} = ?",
                    [$phone]
                );


                /*
            | Last 10 digits
            */

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanCallingPhone}, 10) = ?",
                        [$phone]
                    );
                }
            })

            ->select([

                'callingorder.id',

                'callingorder.order_id',

                DB::raw("'-' as barcode"),

                DB::raw("'callingorder' as source"),

                DB::raw(
                    'callingorder.product_name as product'
                ),

                'callingorder.amount',
                'callingorder.payment_mode',
                /*
            | Calling status becomes delivery_status
            | for frontend.
            */

                DB::raw(
                    'callingorder.status as delivery_status'
                ),

                'callingorder.created_at',

                'clients.client_name',

                DB::raw(
                    'calling_users.name as staff_name'
                ),

            ])

            ->orderByDesc('callingorder.created_at')

            ->get();


        /*
    |--------------------------------------------------------------------------
    | GET INDIA POST / ORDERS HISTORY
    |--------------------------------------------------------------------------
    |
    | orders.delivery_status:
    |
    | In Transit
    | RTO
    | Delivered
    | Out for Delivery
    | etc.
    |
    */

        $indiaPostOrders = DB::table('orders')

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->where(function ($query) use (
                $cleanOrdersPhone,
                $phone
            ) {

                /*
            | Exact normalized phone
            */

                $query->whereRaw(
                    "{$cleanOrdersPhone} = ?",
                    [$phone]
                );


                /*
            | Last 10 digits
            */

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanOrdersPhone}, 10) = ?",
                        [$phone]
                    );
                }
            })

            ->select([

                'orders.id',

                'orders.order_id',

                'orders.barcode',

                DB::raw("'orders' as source"),

                DB::raw(
                    'orders.product as product'
                ),

                'orders.amount',
                'orders.payment_mode',
                /*
            | IMPORTANT:
            | orders uses delivery_status
            */

                DB::raw(
                    'orders.delivery_status as delivery_status'
                ),

                'orders.created_at',

                'clients.client_name',

                DB::raw(
                    "'India Post' as staff_name"
                ),

            ])

            ->orderByDesc('orders.created_at')

            ->get();


        /*
    |--------------------------------------------------------------------------
    | COMBINE BOTH TABLES
    |--------------------------------------------------------------------------
    */

        $history = $callingOrders
            ->concat($indiaPostOrders)
            ->sortByDesc(function ($row) {

                return $row->created_at;
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | CHECK IF INDIA POST ORDER EXISTS
    |--------------------------------------------------------------------------
    */

        $hasIndiaPostOrder =
            $indiaPostOrders->isNotEmpty();


        /*
    |--------------------------------------------------------------------------
    | CHECK VERIFIED CALLING ORDER
    |--------------------------------------------------------------------------
    */

        $hasVerifiedCallingOrder =
            $callingOrders->contains(function ($row) {

                return strtolower(
                    trim(
                        $row->delivery_status ?? ''
                    )
                ) === 'verified';
            });


        /*
    |--------------------------------------------------------------------------
    | CHECK CALLING ORDER STATUS
    |--------------------------------------------------------------------------
    |
    | We want to hide the new order form only when
    | the customer's calling history is ONLY verified
    | and there is NO India Post order yet.
    |
    */

        $allCallingOrdersAreVerified =
            $callingOrders->isNotEmpty() &&
            $callingOrders->every(function ($row) {

                return strtolower(
                    trim(
                        $row->delivery_status ?? ''
                    )
                ) === 'verified';
            });


        /*
    |--------------------------------------------------------------------------
    | HIDE NEW ORDER FORM
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Callingorder:
    | Verified
    |
    | Orders:
    | Nothing
    |
    | => HIDE FORM
    |
    |
    | If India Post order exists:
    |
    | Verified
    | +
    | RTO / In Transit / Delivered
    |
    | => SHOW FORM
    |
    */

        $hideCallForm =
            $hasVerifiedCallingOrder &&
            $allCallingOrdersAreVerified &&
            !$hasIndiaPostOrder;


        /*
    |--------------------------------------------------------------------------
    | AUTO FILL CUSTOMER DATA
    |--------------------------------------------------------------------------
    |
    | Get latest calling order for customer.
    |
    */

        $latestCallingOrder = DB::table('callingorder')

            ->where(function ($query) use (
                $cleanCallingPhone,
                $phone
            ) {

                $query->whereRaw(
                    "{$cleanCallingPhone} = ?",
                    [$phone]
                );

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanCallingPhone}, 10) = ?",
                        [$phone]
                    );
                }
            })

            ->orderByDesc(
                'callingorder.created_at'
            )

            ->select([

                'callingorder.client_id',

                'callingorder.order_id',

                'callingorder.customer_name',

                'callingorder.father_name',

                'callingorder.age',

                'callingorder.customer_phone',

                'callingorder.shipping_address',

                'callingorder.city',

                'callingorder.state',

                'callingorder.pincode',

                DB::raw(
                    'callingorder.product_name as product'
                ),

                'callingorder.quantity',

                'callingorder.amount',

                'callingorder.payment_mode',

                DB::raw(
                    'callingorder.status as delivery_status'
                ),

                'callingorder.created_at',

            ])

            ->first();


        /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'success' => true,

            'phone' => $phone,

            'orders' => $history,

            'delivered_order' => $latestCallingOrder,

            'hide_call_form' => $hideCallForm,

            'has_india_post_order' => $hasIndiaPostOrder,

            'has_verified_calling_order' =>
            $hasVerifiedCallingOrder,

        ]);
    }

    public function getClientProducts($clientId)
    {
        $products = ClientProduct::where(
            'client_id',
            $clientId
        )
            ->select(
                'id',
                'shopify_product_name',
                'weight_per_unit'
            )
            ->orderBy('shopify_product_name')
            ->get();

        return response()->json($products);
    }

    private function generateOrderId($staff, $selectedDate)
    {
        $name = trim($staff->name);

        $shortName =
            strtoupper(substr($name, 0, 1)) .
            strtolower(substr($name, -1));

        $date = $selectedDate->format('d-m-y');

        // Get the highest existing number for this staff + date
        $prefix = $shortName . '-' . $date . '-';

        $lastOrder = CallingOrder::where('assigned_to', $staff->id)
            ->where('order_id', 'like', $prefix . '%')
            ->orderByRaw("
            CAST(
                SUBSTRING_INDEX(order_id, '-', -1)
                AS UNSIGNED
            ) DESC
        ")
            ->first();

        if ($lastOrder) {

            $lastNumber = (int) substr(
                $lastOrder->order_id,
                strrpos($lastOrder->order_id, '-') + 1
            );

            $count = $lastNumber + 1;
        } else {

            $count = 1;
        }

        // Make absolutely sure the ID is unique
        do {

            $orderId = $prefix . $count;

            $exists = CallingOrder::where(
                'order_id',
                $orderId
            )->exists();

            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $orderId;
    }


    /*
    |--------------------------------------------------------------------------
    | Save Lead / Order
    |--------------------------------------------------------------------------
    */
    public function previewOrderId(Request $request)
    {
        $staff = Auth::guard('calling_user')->user();

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
        ]);

        $selectedDate = \Carbon\Carbon::parse($request->date);

        // Your existing private function
        $orderId = $this->generateOrderId(
            $staff,
            $selectedDate
        );

        return response()->json([
            'success'  => true,
            'order_id' => $orderId
        ]);
    }



    public function store(Request $request)
    {
        $staff = Auth::guard('calling_user')->user();

        if (!$staff) {
            abort(403);
        }

        $request->validate([
            'created_at' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'customer_phone' => 'required',
            'status' => 'required|in:verified,pending,not_reachable,same_order,cancel,Other',
        ]);

        if ($request->status === 'verified') {
            $request->validate([
                'customer_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9\s.,\/\-()]+$/'],
                'product_name' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'weight' => 'required|numeric|gt:0',
                'age' => 'required|integer|min:1|max:120',
                'amount' => 'required|numeric|gt:0',
                'payment_mode' => 'required|in:COD,VPP,Prepaid',
                'pincode' => 'required|regex:/^[0-9]{6}$/',
                'city' => 'required|string|max:255',
                //'state' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s.\-]+$/'],
                'address' => ['required', 'string', 'max:1000', 'regex:/^[A-Za-z0-9\s.,\/\-#()]+$/'],
            ]);
        } else {
            $request->validate([
                'remarks' => 'required|string|max:1000',
            ]);
        }

        try {
            $selectedDate = Carbon::parse($request->created_at);
            $phone = $this->normalizePhone($request->customer_phone);

            if (empty($phone)) {
                return back()->withInput()->with(
                    'error',
                    'Please enter a valid customer phone number.'
                );
            }

            if ($request->status === 'verified') {

                /*
            |--------------------------------------------------------------------------
            | 1. DUPLICATE: SAME CLIENT + DATE + PHONE + PRODUCT
            |--------------------------------------------------------------------------
            */

                $existingOrders = CallingOrder::where(
                    'client_id',
                    $request->client_id
                )
                    ->whereDate('order_date', $selectedDate->toDateString())
                    ->where('status', 'verified')
                    ->get([
                        'id',
                        'order_id',
                        'customer_phone',
                        'product_name',
                    ]);

                foreach ($existingOrders as $existing) {
                    $existingPhone = $this->normalizePhone(
                        $existing->customer_phone
                    );

                    $sameProduct = strtolower(trim(
                        (string) $existing->product_name
                    )) === strtolower(trim(
                        (string) $request->product_name
                    ));

                    if (
                        !empty($existingPhone)
                        && $existingPhone === $phone
                        && $sameProduct
                    ) {
                        return back()->withInput()->with(
                            'error',
                            'Duplicate blocked! This customer has already verified this product on ' .
                                $selectedDate->format('d-m-Y') .
                                '. Existing Order ID: ' .
                                ($existing->order_id ?? $existing->id)
                        );
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | 2. CUSTOMER SHIPPING HISTORY
            |--------------------------------------------------------------------------
            */

                $customerOrders = DB::table('orders')
                    ->where('client_id', $request->client_id)
                    ->whereNotNull('customer_phone')
                    ->get([
                        'id',
                        'order_id',
                        'customer_phone',
                        'delivery_status',
                        'payment_mode',
                    ])
                    ->filter(function ($item) use ($phone) {
                        $existingPhone = $this->normalizePhone(
                            $item->customer_phone
                        );

                        return !empty($existingPhone)
                            && $existingPhone === $phone;
                    });

                /*
            |--------------------------------------------------------------------------
            | 3. CUSTOMER CURRENTLY IN TRANSIT
            |--------------------------------------------------------------------------
            | Customer - Intrasit blocks a new verification.
            | RTO-intrasit records are counted below for the prepaid rule.
            |--------------------------------------------------------------------------
            */

                $customerInTransit = $customerOrders->first(
                    function ($item) {
                        $status = strtolower(
                            preg_replace(
                                '/[^a-z]/',
                                '',
                                trim((string) $item->delivery_status)
                            )
                        );

                        return $status === 'customerintrasit';
                    }
                );

                if ($customerInTransit) {
                    return back()->withInput()->with(
                        'error',
                        'Verification blocked! Customer has an order with delivery status "' .
                            $customerInTransit->delivery_status .
                            '". Please check the shipping status before confirming a new order.'
                    );
                }

                /*
            |--------------------------------------------------------------------------
            | 4. COUNT RTO HISTORY
            |--------------------------------------------------------------------------
            */

                $rtoCount = $customerOrders->filter(
                    function ($item) {
                        $status = strtolower(
                            preg_replace(
                                '/[^a-z]/',
                                '',
                                trim((string) $item->delivery_status)
                            )
                        );

                        return $status === 'rtointrasit';
                    }
                )->count();

                /*
            |--------------------------------------------------------------------------
            | 5. TWO OR MORE RTOS = PREPAID ONLY
            |--------------------------------------------------------------------------
            */

                if (
                    $rtoCount >= 2
                    && $request->payment_mode !== 'Prepaid'
                ) {
                    return back()->withInput()->with(
                        'error',
                        'Verification blocked! Customer has ' .
                            $rtoCount .
                            ' RTO records. Only Prepaid payment mode is allowed.'
                    );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | 6. GENERATE ORDER ID
        |--------------------------------------------------------------------------
        */

            $orderId = $this->generateOrderId(
                $staff,
                $selectedDate
            );

            /*
        |--------------------------------------------------------------------------
        | 7. CREATE ORDER
        |--------------------------------------------------------------------------
        */

            if ($request->status === 'verified') {
                $quantity = (int) $request->quantity;
                $weight = (float) $request->weight;

                $data = [
                    'client_id' => $request->client_id,
                    'assigned_to' => $staff->id,
                    'order_id' => $orderId,
                    'order_date' => $selectedDate,
                    'product_name' => $request->product_name,
                    'shopify_product_name' => $request->product_name,
                    'quantity' => $quantity,
                    'weight' => $weight,
                    'total_weight' => $quantity * $weight,
                    'customer_name' => $request->customer_name,
                    'father_name' => $request->father_name,
                    'age' => $request->age,
                    'customer_phone' => $phone,
                    'shipping_address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'pincode' => $request->pincode,
                    'payment_mode' => $request->payment_mode,
                    'amount' => $request->amount,
                    'status' => 'verified',
                    'remarks' => $request->remarks,
                    'order_source' => 'whatsapp',
                    'created_at' => $selectedDate,
                    'updated_at' => $selectedDate,
                ];
            } else {
                $data = [
                    'client_id' => $request->client_id,
                    'assigned_to' => $staff->id,
                    'order_id' => $orderId,
                    'order_date' => $selectedDate,
                    'customer_phone' => $phone,
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'order_source' => 'whatsapp',
                    'created_at' => $selectedDate,
                    'updated_at' => $selectedDate,
                ];
            }

            CallingOrder::create($data);

            return back()->with(
                'success',
                'Lead saved successfully. Order ID: ' . $orderId
            );
        } catch (\Throwable $e) {
            Log::error('Calling order creation failed', [
                'client_id' => $request->client_id,
                'status' => $request->status,
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withInput()->with(
                'error',
                config('app.debug')
                    ? 'Technical error: ' . $e->getMessage()
                    : 'Unable to save lead. Please try again or contact support.'
            );
        }
    }


    public function whatsappOrders(Request $request)
    {
        $staff = $this->callingStaff();

        if (!$staff) {
            abort(403);
        }


        /*
        |--------------------------------------------------------------------------
        | Only logged-in staff records
        |--------------------------------------------------------------------------
        */

        $query = CallingOrder::where(
            'assigned_to',
            $staff->id
        )
            ->where(
                'order_source',
                'whatsapp'
            );


        /*
        |--------------------------------------------------------------------------
        | Client Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('client_id')) {

            $query->where(
                'client_id',
                $request->client_id
            );
        }


        $orders = $query
            ->with('client')
            ->latest()
            ->paginate(100)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Client Filter List
        |--------------------------------------------------------------------------
        */

        $clients = Client::whereIn(

            'id',

            CallingOrder::where(
                'assigned_to',
                $staff->id
            )
                ->where(
                    'order_source',
                    'whatsapp'
                )
                ->select('client_id')

        )
            ->orderBy('client_name')
            ->get();


        return view(
            'calling.orders',
            compact(
                'orders',
                'clients',
                'staff'
            )
        );
    }
}
