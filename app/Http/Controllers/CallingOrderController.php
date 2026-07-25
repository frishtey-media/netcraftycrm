<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\CallingOrder;
use App\Models\Client;
use App\Models\ClientProduct;

class CallingOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Logged In Calling Staff
    |--------------------------------------------------------------------------
    */

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
        $phone = $this->normalizePhone($request->phone);

        if (strlen($phone) !== 10) {

            return response()->json([
                'success' => false,
                'message' => 'Enter valid 10 digit mobile number.',
                'orders'  => [],
                'phone'   => $phone,
            ], 422);
        }


        $orders = DB::table('orders')

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Calling Order
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Staff
            |--------------------------------------------------------------------------
            */

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

            ->whereRaw("
                RIGHT(
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
                    10
                ) = ?
            ", [$phone])

            ->select([

                'orders.order_id',
                'orders.barcode',
                'orders.product',
                'orders.amount',
                'orders.delivery_status',
                'orders.created_at',

                'clients.client_name',

                DB::raw(
                    'MAX(calling_users.name) as staff_name'
                )
            ])

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate rows from join
            |--------------------------------------------------------------------------
            */

            ->groupBy(
                'orders.id',
                'orders.order_id',
                'orders.barcode',
                'orders.product',
                'orders.amount',
                'orders.delivery_status',
                'orders.created_at',
                'clients.client_name'
            )

            ->orderByDesc('orders.created_at')

            ->get();


        return response()->json([

            'success' => true,

            'phone' => $phone,

            'orders' => $orders,

            'count' => $orders->count(),

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
        $staff = $this->callingStaff();

        if (!$staff) {
            abort(403, 'Calling staff login required.');
        }


        /*
        |--------------------------------------------------------------------------
        | Common Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'client_id' =>
            'required|exists:clients,id',

            'created_at' =>
            'required|date',

            'customer_phone' =>
            'required',

            'status' =>
            'required|in:verified,pending,not_reachable,same_order,cancel',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Normalize Phone
        |--------------------------------------------------------------------------
        */

        $phone = $this->normalizePhone(
            $request->customer_phone
        );


        if (strlen($phone) !== 10) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please enter a valid 10 digit mobile number.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Selected Date
        |--------------------------------------------------------------------------
        */

        $selectedDate = Carbon::parse(
            $request->created_at
        );


        /*
        |--------------------------------------------------------------------------
        | Generate Order ID
        |--------------------------------------------------------------------------
        */

        $orderId = $this->generateOrderId(
            $staff,
            $selectedDate
        );


        /*
        |--------------------------------------------------------------------------
        | NON VERIFIED LEAD
        |--------------------------------------------------------------------------
        |
        | Pending
        | Not Reachable
        | Same Order
        | Cancel
        |
        */

        if ($request->status !== 'verified') {

            $request->validate([

                'remarks' =>
                'required|string|max:1000',

            ]);


            CallingOrder::create([

                'client_id' =>
                $request->client_id,

                /*
                 * Logged-in staff only.
                 */

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


            return back()->with(
                'success',
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $request->status
                    )
                ) . ' lead saved successfully.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFIED ORDER VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'customer_name' =>
            'required|string|max:255',

            'age' =>
            'required',

            'product_name' =>
            'required|string',

            'quantity' =>
            'required|integer|min:1',

            'weight' =>
            'nullable|numeric|min:0',

            'amount' =>
            'required|numeric|min:0',

            'payment_mode' =>
            'required',

            'pincode' =>
            'required',

            'address' =>
            'required',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Quantity / Weight
        |--------------------------------------------------------------------------
        */

        $quantity = (int) $request->quantity;

        $weightPerUnit = (float) ($request->weight ?? 0);

        $totalWeight =
            $quantity * $weightPerUnit;


        /*
        |--------------------------------------------------------------------------
        | Save Verified Order
        |--------------------------------------------------------------------------
        */

        CallingOrder::create([

            'client_id' =>
            $request->client_id,

            /*
             * IMPORTANT:
             * Never use assigned_to from request.
             */

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
            $weightPerUnit,

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


        return back()->with(
            'success',
            "Verified order {$orderId} saved successfully."
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
