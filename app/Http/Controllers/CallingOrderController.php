<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\callingorder;
use App\Models\Client;
use App\Models\ClientProduct;

class CallingOrderController extends Controller
{


    private function callingStaff()
    {
        return Auth::guard('calling_user')->user();
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Phone
    |--------------------------------------------------------------------------
    |
    | +91 98034 56598
    | 919803456598
    | 09803456598
    | 98034-56598
    |
    | All become:
    | 9803456598
    |
    */

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


    /*
    |--------------------------------------------------------------------------
    | Customer Previous History
    |--------------------------------------------------------------------------
    */

    public function customerHistory(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Normalize searched phone
    |--------------------------------------------------------------------------
    */

        $phone = $this->normalizePhone($request->phone);

        if (!$phone || strlen($phone) < 7) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number.',
                'orders' => [],
            ], 422);
        }


        /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    |
    | Explicitly use orders.customer_phone.
    | This prevents:
    |
    | Column 'customer_phone' in where clause is ambiguous
    |
    */

        $cleanOrderPhone = "
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
    | Previous Order History
    |--------------------------------------------------------------------------
    */

        $orders = DB::table('orders')

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->leftJoin('callingorder', function ($join) {

                $join->on(
                    'callingorder.order_id',
                    '=',
                    'orders.order_id'
                );

                $join->on(
                    'callingorder.client_id',
                    '=',
                    'orders.client_id'
                );
            })

            ->leftJoin(
                'calling_users',
                'calling_users.id',
                '=',
                'callingorder.assigned_to'
            )

            /*
        |--------------------------------------------------------------------------
        | Phone Search
        |--------------------------------------------------------------------------
        */

            ->where(function ($query) use ($cleanOrderPhone, $phone) {

                // Exact match
                $query->whereRaw(
                    "{$cleanOrderPhone} = ?",
                    [$phone]
                );

                /*
             * Indian compatibility
             *
             * 9803456598
             * +91 9803456598
             * 919803456598
             *
             * all will match.
             */

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanOrderPhone}, 10) = ?",
                        [$phone]
                    );
                }
            })

            ->select([

                'orders.order_id',

                'orders.barcode',

                'orders.product',

                'orders.amount',

                'orders.delivery_status',

                'orders.created_at',

                'clients.client_name',

                DB::raw(
                    'calling_users.name as staff_name'
                ),
            ])

            ->orderByDesc(
                'orders.created_at'
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | Latest Delivered Order
    |--------------------------------------------------------------------------
    |
    | Is query me JOIN nahi hai.
    | Still orders.customer_phone explicitly use kar rahe hain.
    |
    */

        $deliveredOrder = DB::table('orders')

            ->where(function ($query) use ($cleanOrderPhone, $phone) {

                $query->whereRaw(
                    "{$cleanOrderPhone} = ?",
                    [$phone]
                );

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanOrderPhone}, 10) = ?",
                        [$phone]
                    );
                }
            })

            ->whereRaw(
                "LOWER(TRIM(orders.delivery_status)) = ?",
                ['delivered']
            )

            ->orderByDesc(
                'orders.created_at'
            )

            ->select([

                'orders.client_id',

                'orders.order_id',

                'orders.customer_name',

                'orders.father_name',

                'orders.age',

                'orders.customer_phone',

                'orders.shipping_address',

                'orders.city',

                'orders.state',

                'orders.pincode',

                'orders.product',

                'orders.quantity',

                'orders.amount',

                'orders.payment_mode',

                'orders.delivery_status',

                'orders.created_at',
            ])

            ->first();


        /*
    |--------------------------------------------------------------------------
    | Final Response
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'success' => true,

            'phone' => $phone,

            'orders' => $orders,

            'delivered_order' => $deliveredOrder,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Client Products
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Generate Order ID
    |--------------------------------------------------------------------------
    */

    private function generateOrderId($staff, $selectedDate)
    {
        $name = trim($staff->name);

        $shortName =
            strtoupper(substr($name, 0, 1))
            .
            strtolower(substr($name, -1));

        $date = $selectedDate->format('d-m-y');


        /*
        |--------------------------------------------------------------------------
        | Staff-wise + Date-wise count
        |--------------------------------------------------------------------------
        */

        $count = CallingOrder::where(
            'assigned_to',
            $staff->id
        )
            ->whereDate(
                'created_at',
                $selectedDate
            )
            ->count() + 1;


        return
            $shortName
            . '-'
            . $date
            . '-'
            . $count;
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


        /*
    |--------------------------------------------------------------------------
    | COMMON VALIDATION
    |--------------------------------------------------------------------------
    */

        $request->validate([

            'created_at' =>
            'required|date',

            'client_id' =>
            'required|exists:clients,id',

            'customer_phone' =>
            'required',

            'status' =>
            'required|in:verified,pending,not_reachable,same_order,cancel,Other',

        ]);


        /*
    |--------------------------------------------------------------------------
    | VERIFIED VALIDATION
    |--------------------------------------------------------------------------
    */

        if ($request->status === 'verified') {

            $request->validate([

                'customer_name' =>
                'required|string|max:255',

                'product_name' =>
                'required|string',

                'quantity' =>
                'required|integer|min:1',

                'age' =>
                'required',

                'amount' =>
                'required|numeric',

                'payment_mode' =>
                'required',

                'pincode' =>
                'required',

                'address' =>
                'required',

            ]);
        } else {

            /*
        |--------------------------------------------------------------------------
        | NON VERIFIED
        |--------------------------------------------------------------------------
        */

            $request->validate([

                'remarks' =>
                'required|string|max:1000',

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

        $selectedDate =
            Carbon::parse(
                $request->created_at
            );


        /*
    |--------------------------------------------------------------------------
    | GENERATE ORDER ID
    |--------------------------------------------------------------------------
    */

        $orderId =
            $this->generateOrderId(
                $staff,
                $selectedDate
            );


        /*
    |--------------------------------------------------------------------------
    | NORMALIZE PHONE
    |--------------------------------------------------------------------------
    */

        $phone =
            $this->normalizePhone(
                $request->customer_phone
            );


        /*
    |--------------------------------------------------------------------------
    | VERIFIED
    |--------------------------------------------------------------------------
    */

        if ($request->status === 'verified') {

            $quantity =
                (int) $request->quantity;

            $weight =
                (float) ($request->weight ?? 0);

            $totalWeight =
                $quantity * $weight;


            CallingOrder::create([

                'client_id' =>
                $request->client_id,

                'assigned_to' =>
                $staff->id,

                'order_id' =>
                $orderId,

                'order_date' =>
                $selectedDate,

                'product_name' =>
                $request->product_name,

                'shopify_product_name' =>
                $request->product_name,

                'quantity' =>
                $quantity,

                'weight' =>
                $weight,

                'total_weight' =>
                $totalWeight,

                'customer_name' =>
                $request->customer_name,

                'father_name' =>
                $request->father_name,

                'age' =>
                $request->age,

                'customer_phone' =>
                $phone,

                'shipping_address' =>
                $request->address,

                'city' =>
                $request->city,

                'state' =>
                $request->state,

                'pincode' =>
                $request->pincode,

                'payment_mode' =>
                $request->payment_mode,

                'amount' =>
                $request->amount,

                'status' =>
                'verified',

                'remarks' =>
                $request->remarks,

                'order_source' =>
                'whatsapp',

                'created_at' =>
                $selectedDate,

                'updated_at' =>
                $selectedDate,
            ]);
        } else {

            /*
        |--------------------------------------------------------------------------
        | NON VERIFIED LEAD
        |--------------------------------------------------------------------------
        */

            CallingOrder::create([

                'client_id' =>
                $request->client_id,

                'assigned_to' =>
                $staff->id,

                'order_id' =>
                $orderId,

                'order_date' =>
                $selectedDate,

                'customer_phone' =>
                $phone,

                'status' =>
                $request->status,

                'remarks' =>
                $request->remarks,

                'order_source' =>
                'whatsapp',

                'created_at' =>
                $selectedDate,

                'updated_at' =>
                $selectedDate,
            ]);
        }


        return redirect()
            ->back()
            ->with(
                'success',
                'Lead saved successfully. Order ID: ' . $orderId
            );
    }


    /*
    |--------------------------------------------------------------------------
    | My WhatsApp Orders
    |--------------------------------------------------------------------------
    */

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
