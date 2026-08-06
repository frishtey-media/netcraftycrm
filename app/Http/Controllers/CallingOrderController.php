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
    | Previous Order History
    |--------------------------------------------------------------------------
    */

        $orders = DB::table('callingorder')

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

            ->where(function ($query) use ($cleanOrderPhone, $phone) {

                $query->whereRaw(
                    "{$cleanOrderPhone} = ?",
                    [$phone]
                );

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanOrderPhone},10)=?",
                        [$phone]
                    );
                }
            })

            ->select([

                'callingorder.order_id',

                'callingorder.barcode',

                DB::raw('callingorder.product_name as product'),

                'callingorder.amount',

                DB::raw('callingorder.status as delivery_status'),

                'callingorder.created_at',

                'clients.client_name',

                DB::raw('calling_users.name as staff_name'),
            ])

            ->orderByDesc('callingorder.created_at')

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

        $deliveredOrder = DB::table('callingorder')

            ->where(function ($query) use ($cleanOrderPhone, $phone) {

                $query->whereRaw(
                    "{$cleanOrderPhone} = ?",
                    [$phone]
                );

                if (strlen($phone) === 10) {

                    $query->orWhereRaw(
                        "RIGHT({$cleanOrderPhone},10)=?",
                        [$phone]
                    );
                }
            })

            ->orderByDesc('callingorder.created_at')

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

                DB::raw('callingorder.product_name as product'),

                'callingorder.quantity',

                'callingorder.amount',

                'callingorder.payment_mode',

                DB::raw('callingorder.status as delivery_status'),

                'callingorder.created_at',
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


        if ($request->status === 'verified') {

            $request->validate([

                'customer_name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s.,\/\-()]+$/'
                ],




                'product_name' => [
                    'required',
                    'string',
                ],

                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'weight' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'age' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:120',
                ],

                'amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'payment_mode' => [
                    'required',
                    'in:COD,VPP,Prepaid',
                ],

                'pincode' => [
                    'required',
                    'regex:/^[0-9]{6}$/',
                ],

                'city' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z\s.\-]+$/',
                ],

                'state' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[A-Za-z\s.\-]+$/',
                ],

                'address' => [
                    'required',
                    'string',
                    'max:1000',
                    'regex:/^[A-Za-z0-9\s.,\/\-#()]+$/',
                ],

            ], [

                'customer_name.required' => 'Customer name is required.',
                'customer_name.regex' => 'Customer name must be entered in English only.',




                'product_name.required' => 'Please select a product.',

                'quantity.required' => 'Quantity is required.',
                'quantity.min' => 'Quantity must be at least 1.',

                'weight.required' => 'Weight is required.',
                'weight.gt' => 'Weight must be greater than 0.',

                'age.required' => 'Age is required.',
                'age.max' => 'Please enter a valid age.',

                'amount.required' => 'Amount is required.',
                'amount.gt' => 'Amount must be greater than 0.',

                'payment_mode.required' => 'Please select payment mode.',

                'pincode.required' => 'Pincode is required.',
                'pincode.regex' => 'Pincode must contain exactly 6 digits.',

                'city.required' => 'City is required.',
                'city.regex' => 'City must be entered in English only.',

                'state.required' => 'State is required.',
                'state.regex' => 'State must be entered in English only.',

                'address.required' => 'Shipping address is required.',
                'address.regex' => 'Shipping address must be entered in English only.',

            ]);
        } else {

            $request->validate([

                'remarks' =>
                'required|string|max:1000',

            ]);
        }

        $selectedDate =
            Carbon::parse(
                $request->created_at
            );

        $orderId =
            $this->generateOrderId(
                $staff,
                $selectedDate
            );

        $phone =
            $this->normalizePhone(
                $request->customer_phone
            );

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
