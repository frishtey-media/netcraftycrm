<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\callingorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\KnowlarityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\ClientProduct;

class CallingOrderApiController extends Controller
{
    private function generateOrderId($staff, $selectedDate)
    {
        $name = trim($staff->name);

        $shortName =
            strtoupper(substr($name, 0, 1)) .
            strtolower(substr($name, -1));

        $date = $selectedDate->format('d-m-y');

        // Get the highest existing number for this staff + date
        $prefix = $shortName . '-' . $date . '-';

        $lastOrder = CallingOrder::where(
            'assigned_to',
            $staff->id
        )
            ->where(
                'order_id',
                'like',
                $prefix . '%'
            )
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
    private function apiCallingStaff(Request $request)
    {
        return $request->user()
            ?: Auth::guard('calling_user')->user();
    }
    public function verifiedOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $userId = $user->id;

        // DEBUG
        $totalVerified = CallingOrder::whereRaw(
            "LOWER(TRIM(status)) = ?",
            ['verified']
        )->count();

        $myVerified = CallingOrder::where('assigned_to', $userId)
            ->whereRaw(
                "LOWER(TRIM(status)) = ?",
                ['verified']
            )
            ->count();

        $orders = CallingOrder::where('assigned_to', $userId)
            ->whereRaw(
                "LOWER(TRIM(status)) = ?",
                ['verified']
            )
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,

            'debug' => [
                'logged_in_user_id' => $userId,
                'logged_in_user_name' => $user->name,
                'total_verified_all_staff' => $totalVerified,
                'my_verified_orders' => $myVerified,
            ],

            'data' => [
                'orders' => $orders,
                'clients' => [],
                'status_label' => 'Verified',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function sameOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userId = $user->id;

        /*
    |--------------------------------------------------------------------------
    | Client Tabs
    |--------------------------------------------------------------------------
    */

        $clients = CallingOrder::query()
            ->select(
                'client_id',
                DB::raw('COUNT(*) as total')
            )
            ->where('assigned_to', $userId)
            ->where('status', 'same_order')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {

                return [
                    'client_id' => $row->client_id,

                    'client_name' =>
                    $row->client->client_name
                        ?? 'Client',

                    'total' => (int) $row->total,
                ];
            });


        /*
    |--------------------------------------------------------------------------
    | Orders Query
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('status', 'same_order');


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


        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'customer_phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'product_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'city',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'state',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'pincode',
                        'like',
                        "%{$search}%"
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->latest('created_at')
            ->paginate(50);


        /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'success' => true,

            'data' => [

                'orders' => $orders,

                'clients' => $clients,

                'status_label' => 'Same Order',

                'status_count' => $orders->total(),

            ]

        ]);
    }

    public function customerSearch(Request $request)
    {
        $staff = Auth::guard('calling_user')->user();

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.'
            ], 401);
        }

        $request->validate([
            'customer_phone' => [
                'required',
                'string',
                'max:20'
            ],
        ]);

        $phone = preg_replace('/\D+/', '', $request->customer_phone);

        if (strlen($phone) === 12 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        if (strlen($phone) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer phone number.'
            ], 422);
        }

        $orders = CallingOrder::where(
            'customer_phone',
            $phone
        )
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'orders' => $orders,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
            ],
        ]);
    }
    public function cancelOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        // Client-wise counts
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('status', 'cancel')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' =>
                    $row->client?->client_name
                        ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        // Orders
        $query = CallingOrder::where('assigned_to', $userId)
            ->where('status', 'cancel');

        // Client filter
        if ($request->filled('client_id')) {
            $query->where(
                'client_id',
                $request->client_id
            );
        }

        // Search
        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'customer_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_phone',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'product_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'city',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'state',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'pincode',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $orders = $query
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Cancel orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Cancel Order',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function notReachableOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('status', 'not_reachable')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,

                    'client_name' =>
                    $row->client?->client_name
                        ?? 'Client',

                    'total' => (int) $row->total,
                ];
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::where(
            'assigned_to',
            $userId
        )
            ->where(
                'status',
                'not_reachable'
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


        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'customer_phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'product_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'city',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'state',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'pincode',
                        'like',
                        "%{$search}%"
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();


        return response()->json([
            'success' => true,

            'message' =>
            'Not Reachable orders fetched successfully.',

            'data' => [

                'orders' => $orders,

                'clients' => $clients,

                'status_label' =>
                'Not Reachable',

                'status_count' =>
                $orders->total(),
            ],
        ]);
    }
    public function notReachableOrderStatus(
        Request $request,
        int $id
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel'
            ],
        ]);

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('status', 'not_reachable')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Not Reachable order not found.',
            ], 404);
        }

        $order->status = $validated['status'];
        $order->save();

        return response()->json([
            'success' => true,
            'message' =>
            'Order status updated successfully.',

            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }
    public function notReachableOrderUpdate(
        Request $request,
        int $id
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([

            'customer_name' => [
                'required',
                'string',
                'max:255'
            ],

            'customer_phone' => [
                'required',
                'digits:10'
            ],

            'product_name' => [
                'required',
                'string',
                'max:255'
            ],

            'father_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'payment_mode' => [
                'required',
                'in:COD,Prepaid'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'age' => [
                'required',
                'integer',
                'min:1',
                'max:120'
            ],

            'city' => [
                'required',
                'string',
                'max:255'
            ],

            'state' => [
                'required',
                'string',
                'max:255'
            ],

            'pincode' => [
                'required',
                'digits:6'
            ],

            'shipping_address' => [
                'required',
                'string',
                'max:1000'
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ]);


        $order = CallingOrder::where('id', $id)
            ->where(
                'assigned_to',
                $user->id
            )
            ->where(
                'status',
                'not_reachable'
            )
            ->first();


        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Not Reachable order not found.',
            ], 404);
        }


        /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

        $order->customer_name =
            $validated['customer_name'];

        $order->customer_phone =
            $validated['customer_phone'];

        $order->product_name =
            $validated['product_name'];

        $order->father_name =
            $validated['father_name'] ?? null;

        $order->quantity =
            $validated['quantity'];

        $order->payment_mode =
            $validated['payment_mode'];

        $order->amount =
            $validated['amount'];

        $order->age =
            $validated['age'];

        $order->city =
            $validated['city'];

        $order->state =
            $validated['state'];

        $order->pincode =
            $validated['pincode'];

        $order->shipping_address =
            $validated['shipping_address'];

        $order->remarks =
            $validated['remarks'] ?? null;

        $order->save();


        return response()->json([
            'success' => true,

            'message' =>
            'Not Reachable order updated successfully.',

            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }

    public function whatsappOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $staffId = $user->id;


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP ORDERS
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::query()
            ->where(
                'assigned_to',
                $staffId
            )
            ->where(
                'order_source',
                'whatsapp'
            );


        /*
    |--------------------------------------------------------------------------
    | CLIENT FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->filled('client_id')) {

            $query->where(
                'client_id',
                $request->client_id
            );
        }


        /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'customer_phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'product_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'city',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'state',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'pincode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'shipping_address',
                        'like',
                        "%{$search}%"
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | ORDERS
    |--------------------------------------------------------------------------
    */

        $perPage = (int) $request->get(
            'per_page',
            20
        );

        $perPage = min(
            max($perPage, 10),
            100
        );


        $orders = $query
            ->with('client')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | CLIENT LIST
    |--------------------------------------------------------------------------
    */

        $clients = CallingOrder::query()
            ->select(
                'client_id',
                DB::raw(
                    'COUNT(*) as total'
                )
            )
            ->where(
                'assigned_to',
                $staffId
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {

                return [
                    'id' => $row->client_id,

                    'client_name' =>
                    $row->client?->client_name
                        ?? 'Client',

                    'total' =>
                    (int) $row->total,
                ];
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'success' => true,

            'message' =>
            'WhatsApp orders fetched successfully.',

            'data' => [

                'orders' => $orders,

                'clients' => $clients,

                'status_label' =>
                'WhatsApp Orders',

                'status_count' =>
                $orders->total(),
            ],

        ]);
    }

    public function whatsappOrderStatus(
        Request $request,
        int $id
    ) {

        $user = $request->user();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }


        $validated = $request->validate([

            'status' => [
                'required',

                'in:
                verified,
                same_order,
                not_reachable,
                cancel'
            ],

        ]);


        $order = CallingOrder::query()
            ->where(
                'id',
                $id
            )
            ->where(
                'assigned_to',
                $user->id
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->first();


        if (!$order) {

            return response()->json([
                'success' => false,
                'message' =>
                'WhatsApp order not found.',
            ], 404);
        }


        $order->status =
            $validated['status'];

        $order->save();


        return response()->json([

            'success' => true,

            'message' =>
            'Order status updated successfully.',

            'data' => [
                'order' =>
                $order->fresh(),
            ],

        ]);
    }

    public function whatsappOrderUpdate(
        Request $request,
        int $id
    ) {

        $user = $request->user();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' =>
                'Unauthenticated.',
            ], 401);
        }


        $validated = $request->validate([

            'customer_name' => [
                'required',
                'string',
                'max:255'
            ],

            'customer_phone' => [
                'required',
                'digits:10'
            ],

            'product_name' => [
                'required',
                'string',
                'max:255'
            ],

            'father_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'payment_mode' => [
                'required',
                'in:COD,Prepaid'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'age' => [
                'required',
                'integer',
                'min:1',
                'max:120'
            ],

            'city' => [
                'required',
                'string',
                'max:255'
            ],

            'state' => [
                'required',
                'string',
                'max:255'
            ],

            'pincode' => [
                'required',
                'digits:6'
            ],

            'shipping_address' => [
                'required',
                'string',
                'max:1000'
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000'
            ],

        ]);


        $order = CallingOrder::query()
            ->where(
                'id',
                $id
            )
            ->where(
                'assigned_to',
                $user->id
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->first();


        if (!$order) {

            return response()->json([
                'success' => false,
                'message' =>
                'WhatsApp order not found.',
            ], 404);
        }


        /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

        $order->customer_name =
            $validated['customer_name'];

        $order->customer_phone =
            $validated['customer_phone'];

        $order->product_name =
            $validated['product_name'];

        $order->father_name =
            $validated['father_name'] ?? null;

        $order->quantity =
            $validated['quantity'];

        $order->payment_mode =
            $validated['payment_mode'];

        $order->amount =
            $validated['amount'];

        $order->age =
            $validated['age'];

        $order->city =
            $validated['city'];

        $order->state =
            $validated['state'];

        $order->pincode =
            $validated['pincode'];

        $order->shipping_address =
            $validated['shipping_address'];

        $order->remarks =
            $validated['remarks'] ?? null;


        $order->save();


        return response()->json([

            'success' => true,

            'message' =>
            'WhatsApp order updated successfully.',

            'data' => [

                'order' =>
                $order->fresh(),

            ],

        ]);
    }
    public function manualOrderStore(Request $request)
    {
        $staff = $this->apiCallingStaff($request);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.',
            ], 403);
        }

        $request->validate([
            'created_at' => ['required', 'date'],
            'client_id' => ['required', 'exists:clients,id'],
            'customer_phone' => ['required', 'string'],
            'status' => [
                'required',
                'in:verified,pending,not_reachable,same_order,cancel,Other',
            ],
        ]);

        $selectedDate = Carbon::parse($request->created_at);
        $phone = $this->normalizePhone($request->customer_phone);

        if (strlen($phone) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid customer phone number.',
            ], 422);
        }

        if ($request->status === 'verified') {
            $request->validate([
                'customer_name' => ['required', 'string', 'max:255'],
                'product_name' => ['required', 'string', 'max:255'],
                'quantity' => ['required', 'integer', 'min:1'],
                'weight' => ['required', 'numeric', 'gt:0'],
                'age' => ['required', 'integer', 'min:1', 'max:120'],
                'amount' => ['required', 'numeric', 'gt:0'],
                'payment_mode' => ['required', 'in:COD,VPP,Prepaid'],
                'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:1000'],
                'remarks' => ['nullable', 'string', 'max:1000'],
            ]);

            $duplicate = CallingOrder::where('client_id', $request->client_id)
                ->whereDate('order_date', $selectedDate->format('Y-m-d'))
                ->where('customer_phone', $phone)
                ->where('status', 'verified')
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'This customer number is already verified for this client on ' .
                        $selectedDate->format('d-m-Y') .
                        '. Same customer/order cannot be verified again.',
                ], 422);
            }
        } else {
            $request->validate([
                'remarks' => ['required', 'string', 'max:1000'],
            ]);
        }

        $orderId = $this->generateOrderId($staff, $selectedDate);

        $data = [
            'client_id' => $request->client_id,
            'assigned_to' => $staff->id,
            'order_id' => $orderId,
            'order_date' => $selectedDate,
            'customer_phone' => $phone,
            'status' => $request->status,
            'remarks' => $request->remarks ?? null,
            'order_source' => 'whatsapp',
            'created_at' => $selectedDate,
            'updated_at' => now(),
        ];

        if ($request->status === 'verified') {
            $quantity = (int) $request->quantity;
            $weight = (float) $request->weight;

            $data = array_merge($data, [
                'product_name' => $request->product_name,
                'shopify_product_name' => $request->product_name,
                'quantity' => $quantity,
                'weight' => $weight,
                'total_weight' => $quantity * $weight,
                'customer_name' => $request->customer_name,
                'father_name' => $request->father_name,
                'age' => $request->age,
                'shipping_address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'payment_mode' => $request->payment_mode,
                'amount' => $request->amount,
            ]);
        }

        $order = CallingOrder::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Order saved successfully.',
            'data' => [
                'order' => $order->fresh('client'),
                'order_id' => $orderId,
            ],
        ]);
    }

    public function manualCustomerSearch(Request $request)
    {
        $staff = $this->apiCallingStaff($request);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.',
            ], 403);
        }

        $request->validate([
            'customer_phone' => ['required', 'string'],
        ]);

        $phone = $this->normalizePhone(
            $request->customer_phone
        );

        if (strlen($phone) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 10 digit mobile number.',
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Search all common phone formats
    |--------------------------------------------------------------------------
    */

        $phoneFormats = [
            $phone,
            '0' . $phone,
            '91' . $phone,
            '+91' . $phone,
        ];

        $orders = CallingOrder::with('client')
            ->where(function ($query) use ($phoneFormats) {

                foreach ($phoneFormats as $index => $number) {

                    if ($index === 0) {
                        $query->where(
                            'customer_phone',
                            $number
                        );
                    } else {
                        $query->orWhere(
                            'customer_phone',
                            $number
                        );
                    }
                }
            })
            ->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->get();

        $latest = $orders->first();

        return response()->json([
            'success' => true,

            'data' => [
                'phone' => $phone,

                /*
             * IMPORTANT:
             * Return ALL matching orders.
             */
                'orders' => $orders,

                'customer' => $latest ? [
                    'customer_name' =>
                    $latest->customer_name,

                    'customer_phone' =>
                    $latest->customer_phone,

                    'father_name' =>
                    $latest->father_name,

                    'age' =>
                    $latest->age,

                    'city' =>
                    $latest->city,

                    'state' =>
                    $latest->state,

                    'pincode' =>
                    $latest->pincode,

                    'shipping_address' =>
                    $latest->shipping_address,
                ] : null,
            ],
        ]);
    }

    public function manualOrderClients(Request $request)
    {
        if (!$this->apiCallingStaff($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.',
            ], 403);
        }

        $clients = Client::query()
            ->orderBy('client_name')
            ->get(['id', 'client_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'clients' => $clients,
            ],
        ]);
    }
    public function manualClientProducts(Request $request, $clientId)
    {
        if (!$this->apiCallingStaff($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.',
            ], 403);
        }

        $client = Client::find($clientId);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        $products = ClientProduct::query()
            ->where('client_id', $client->id)
            ->whereNotNull('shopify_product_name')
            ->where('shopify_product_name', '!=', '')
            ->orderBy('shopify_product_name')
            ->get([
                'id',
                'shopify_product_name',
                'weight_per_unit',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'client_name' => $client->client_name,
                ],
                'products' => $products,
            ],
        ]);
    }

    public function manualPreviewOrderId(Request $request)
    {
        $staff = $this->apiCallingStaff($request);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Calling staff login required.',
            ], 403);
        }

        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : now();

        $orderId = $this->generateOrderId($staff, $selectedDate);

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $orderId,
                'date' => $selectedDate->format('Y-m-d'),
            ],
        ]);
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
    private function getStaffDeliveryOrders(
        Request $request,
        string $deliveryStatus
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;


        $query = DB::table('callingorder as co')

            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )

            ->leftJoin('calling_logs as cl', function ($join) {

                $join->on(
                    'cl.order_id',
                    '=',
                    'co.order_id'
                );

                $join->on(
                    'cl.staff_id',
                    '=',
                    'co.assigned_to'
                );

                $join->whereRaw('
                    cl.id = (
                        SELECT MAX(cl2.id)
                        FROM calling_logs AS cl2
                        WHERE cl2.order_id = co.order_id
                        AND cl2.staff_id = co.assigned_to
                    )
                ');
            })

            ->where(
                'co.assigned_to',
                $userId
            )

            ->where(
                'o.delivery_status',
                $deliveryStatus
            );


        /*
        |--------------------------------------------------------------------------
        | Client Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('client_id')) {

            $query->where(
                'co.client_id',
                $request->client_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'o.customer_name',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'o.customer_phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'co.order_id',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.product',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.city',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'o.pincode',
                        'like',
                        "%{$search}%"
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Select
        |--------------------------------------------------------------------------
        */

        $query->select(
            'co.id',
            'co.order_id',
            'co.client_id',
            'co.assigned_to',

            'o.customer_name',
            'o.customer_phone',
            'o.shipping_address',
            'o.city',
            'o.barcode',
            'o.state',
            'o.pincode',
            'o.delivery_remark',

            'o.product as product_name',
            'o.quantity',
            'o.amount',
            'o.payment_mode',
            'o.date as order_date',
            'o.delivery_status',

            'cl.id as call_log_id',
            'cl.call_status',
            'cl.called_at'
        );


        /*
        |--------------------------------------------------------------------------
        | Latest Orders
        |--------------------------------------------------------------------------
        */

        $query->orderByDesc(
            'o.date'
        );


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        return $query
            ->paginate(20)
            ->withQueryString();
    }

    public function sidebarCounts(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $outForDelivery = DB::table('callingorder as co')
            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'Out for Delivery')
            ->count();

        $onHold = DB::table('callingorder as co')
            ->join(
                'orders as o',
                'o.order_id',
                '=',
                'co.order_id'
            )
            ->where('co.assigned_to', $userId)
            ->where('o.delivery_status', 'On Hold')
            ->count();

        return response()->json([
            'success' => true,

            'data' => [
                'out_for_delivery' => $outForDelivery,
                'on_hold' => $onHold,
            ],
        ]);
    }


    public function onHold(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'On Hold'
        );

        return response()->json([
            'success' => true,
            'message' => 'On Hold orders fetched successfully.',
            'data' => $orders,
        ]);
    }
    public function pendingOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = (int) $user->id;

        // Client-wise pending count for the logged-in staff only.
        $clients = DB::table('callingorder as co')
            ->leftJoin('clients as c', 'c.id', '=', 'co.client_id')
            ->where('co.assigned_to', $userId)
            ->where('co.status', 'pending')
            ->select(
                'co.client_id',
                DB::raw("COALESCE(c.client_name, 'Client') as client_name"),
                DB::raw('COUNT(co.id) as total')
            )
            ->groupBy('co.client_id', 'c.client_name')
            ->orderBy('client_name')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id !== null ? (int) $row->client_id : null,
                    'client_name' => (string) $row->client_name,
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";

                $q->where('order_id', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhere('shipping_address', 'like', $like)
                    ->orWhere('pincode', 'like', $like);
            });
        }

        $orders = $query
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Pending orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Pending',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function updatePendingOrderStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::query()
            ->where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found or not assigned to this staff member.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }

    public function updatePendingOrder(Request $request, int $id)
    {
        $request->validate([
            'product_name' => 'nullable|string|max:1000',
            'quantity' => 'nullable|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'shipping_address' => 'nullable|string|max:2000',
            'age' => 'nullable|integer|min:0|max:150',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'payment_mode' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::query()
            ->where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found or not assigned to this staff member.',
            ], 404);
        }

        $order->update([
            'product_name' => $request->product_name,
            'quantity' => $request->quantity,
            'customer_name' => $request->customer_name,
            'father_name' => $request->father_name,
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
            'data' => [
                'order' => $order->fresh(),
            ],
        ]);
    }
    public function trackCall(Request $request)
    {
        $request->validate([
            'callingorder_id' => [
                'required',
                'integer',
            ],

            'order_id' => [
                'required',
                'string',
            ],

            'customer_phone' => [
                'required',
                'string',
            ],

            'call_status' => [
                'nullable',
                'string',
            ],
        ]);


        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }


        /*
    |--------------------------------------------------------------------------
    | Verify order belongs to logged-in staff
    |--------------------------------------------------------------------------
    */

        $callingOrder = DB::table('callingorder')
            ->where('id', $request->callingorder_id)
            ->where(
                'assigned_to',
                $user->id
            )
            ->where(
                'order_id',
                $request->order_id
            )
            ->first();


        if (!$callingOrder) {

            return response()->json([
                'success' => false,
                'message' => 'Order is not assigned to this staff member.',
            ], 403);
        }


        /*
    |--------------------------------------------------------------------------
    | Create Call Log
    |--------------------------------------------------------------------------
    */

        $calledAt = now();


        $logId = DB::table('calling_logs')
            ->insertGetId([
                'order_id' => $request->order_id,
                'staff_id' => $user->id,
                'customer_phone' =>
                $request->customer_phone,
                'call_status' =>
                $request->call_status ?? 'called',
                'called_at' => $calledAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


        return response()->json([
            'success' => true,
            'message' => 'Call tracked successfully.',
            'data' => [
                'id' => $logId,
                'called_at' => $calledAt->toDateTimeString(),
                'call_status' =>
                $request->call_status ?? 'called',
            ],
        ]);
    }
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'client_id' => $user->client_id,
                ],
            ],
        ]);
    }

    public function dashboard(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Logged-in Sanctum Staff
    |--------------------------------------------------------------------------
    */

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userId = $user->id;


        /*
    |--------------------------------------------------------------------------
    | Date Filter
    |--------------------------------------------------------------------------
    */

        $fromDate = $request->filled('from')
            ? $request->from
            : now()->format('Y-m-d');

        $toDate = $request->filled('to')
            ? $request->to
            : now()->format('Y-m-d');

        try {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Use YYYY-MM-DD.'
            ], 422);
        }

        if ($from->gt($to)) {
            return response()->json([
                'success' => false,
                'message' => 'From date cannot be greater than To date.'
            ], 422);
        }


        /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

        $baseQuery = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->whereBetween('updated_at', [$from, $to]);


        /*
    |--------------------------------------------------------------------------
    | Source Conditions
    |--------------------------------------------------------------------------
    */

        $webCondition = function ($q) {
            $q->whereNull('order_source')
                ->orWhere('order_source', '')
                ->orWhereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['web']
                );
        };

        $whatsappCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            );
        };

        $rtoCondition = function ($q) {
            $q->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            );
        };

        $deliverReorderCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            );
        };

        $abandonedCondition = function ($q) {
            $q->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            );
        };


        /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

        $countBySource = function (
            $sourceCondition,
            $status = null
        ) use ($baseQuery) {

            $query = clone $baseQuery;

            if ($status !== null) {
                $query->where('status', $status);
            }

            $query->where($sourceCondition);

            return $query->count();
        };


        /*
    |--------------------------------------------------------------------------
    | Total Source Counts
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $baseQuery)->count();

        $webOrders = (clone $baseQuery)
            ->where($webCondition)
            ->count();

        $whatsappOrders = (clone $baseQuery)
            ->where($whatsappCondition)
            ->count();

        $rtoOrders = (clone $baseQuery)
            ->where($rtoCondition)
            ->count();

        $deliveredReorderOrders = (clone $baseQuery)
            ->where($deliverReorderCondition)
            ->count();

        $abandonedOrders = (clone $baseQuery)
            ->where($abandonedCondition)
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Status Counts
    |--------------------------------------------------------------------------
    */

        $pending = (clone $baseQuery)
            ->where('status', 'pending')
            ->count();

        $verified = (clone $baseQuery)
            ->where('status', 'verified')
            ->count();

        $cancelled = (clone $baseQuery)
            ->where('status', 'cancel')
            ->count();

        $notConnected = (clone $baseQuery)
            ->where('status', 'not_reachable')
            ->count();

        $sameOrder = (clone $baseQuery)
            ->where('status', 'same_order')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Other Status
    |--------------------------------------------------------------------------
    */

        $standardStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];

        $other = (clone $baseQuery)
            ->where(function ($q) use ($standardStatuses) {
                $q->whereNotIn('status', $standardStatuses)
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Pending Breakdown
    |--------------------------------------------------------------------------
    */

        $pendingWeb =
            $countBySource($webCondition, 'pending');

        $pendingWhatsapp =
            $countBySource($whatsappCondition, 'pending');

        $pendingRto =
            $countBySource($rtoCondition, 'pending');

        $pendingDeliveredReorder =
            $countBySource($deliverReorderCondition, 'pending');

        $pendingAbandoned =
            $countBySource($abandonedCondition, 'pending');


        /*
    |--------------------------------------------------------------------------
    | Verified Breakdown
    |--------------------------------------------------------------------------
    */

        $verifiedWeb =
            $countBySource($webCondition, 'verified');

        $verifiedWhatsapp =
            $countBySource($whatsappCondition, 'verified');

        $verifiedRto =
            $countBySource($rtoCondition, 'verified');

        $verifiedDeliveredReorder =
            $countBySource($deliverReorderCondition, 'verified');

        $verifiedAbandoned =
            $countBySource($abandonedCondition, 'verified');


        /*
    |--------------------------------------------------------------------------
    | Cancelled Breakdown
    |--------------------------------------------------------------------------
    */

        $cancelledWeb =
            $countBySource($webCondition, 'cancel');

        $cancelledWhatsapp =
            $countBySource($whatsappCondition, 'cancel');

        $cancelledRto =
            $countBySource($rtoCondition, 'cancel');

        $cancelledDeliveredReorder =
            $countBySource($deliverReorderCondition, 'cancel');

        $cancelledAbandoned =
            $countBySource($abandonedCondition, 'cancel');


        /*
    |--------------------------------------------------------------------------
    | Not Connected Breakdown
    |--------------------------------------------------------------------------
    */

        $notConnectedWeb =
            $countBySource($webCondition, 'not_reachable');

        $notConnectedWhatsapp =
            $countBySource($whatsappCondition, 'not_reachable');

        $notConnectedRto =
            $countBySource($rtoCondition, 'not_reachable');

        $notConnectedDeliveredReorder =
            $countBySource(
                $deliverReorderCondition,
                'not_reachable'
            );

        $notConnectedAbandoned =
            $countBySource(
                $abandonedCondition,
                'not_reachable'
            );


        /*
    |--------------------------------------------------------------------------
    | Same Order Breakdown
    |--------------------------------------------------------------------------
    */

        $sameOrderWeb =
            $countBySource($webCondition, 'same_order');

        $sameOrderWhatsapp =
            $countBySource($whatsappCondition, 'same_order');

        $sameOrderRto =
            $countBySource($rtoCondition, 'same_order');

        $sameOrderDeliveredReorder =
            $countBySource(
                $deliverReorderCondition,
                'same_order'
            );

        $sameOrderAbandoned =
            $countBySource(
                $abandonedCondition,
                'same_order'
            );


        /*
    |--------------------------------------------------------------------------
    | Other Breakdown
    |--------------------------------------------------------------------------
    */

        $otherQuery = function (
            $sourceCondition
        ) use ($baseQuery, $standardStatuses) {

            return (clone $baseQuery)
                ->where(function ($q) use ($standardStatuses) {

                    $q->whereNotIn(
                        'status',
                        $standardStatuses
                    )->orWhereNull('status');
                })
                ->where($sourceCondition)
                ->count();
        };

        $otherWeb =
            $otherQuery($webCondition);

        $otherWhatsapp =
            $otherQuery($whatsappCondition);

        $otherRto =
            $otherQuery($rtoCondition);

        $otherDeliveredReorder =
            $otherQuery($deliverReorderCondition);

        $otherAbandoned =
            $otherQuery($abandonedCondition);


        /*
    |--------------------------------------------------------------------------
    | Conversion Rate
    |--------------------------------------------------------------------------
    */

        $successRate = $totalOrders > 0
            ? round(($verified / $totalOrders) * 100, 1)
            : 0;


        /*
    |--------------------------------------------------------------------------
    | API Response
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'success' => true,

            'data' => [

                'staff' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],

                'filters' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],

                'pending_work' => [
                    'web' => $pendingWeb,
                    'whatsapp' => $pendingWhatsapp,
                    'rto' => $pendingRto,
                    'delivered_reorder' => $pendingDeliveredReorder,
                    'abandoned' => $pendingAbandoned,
                ],

                'calling_status' => [

                    'total' => [
                        'count' => $totalOrders,
                        'web' => $webOrders,
                        'whatsapp' => $whatsappOrders,
                        'rto' => $rtoOrders,
                        'delivered_reorder' => $deliveredReorderOrders,
                        'abandoned' => $abandonedOrders,
                    ],

                    'pending' => [
                        'count' => $pending,
                        'web' => $pendingWeb,
                        'whatsapp' => $pendingWhatsapp,
                        'rto' => $pendingRto,
                        'delivered_reorder' => $pendingDeliveredReorder,
                        'abandoned' => $pendingAbandoned,
                    ],

                    'verified' => [
                        'count' => $verified,
                        'web' => $verifiedWeb,
                        'whatsapp' => $verifiedWhatsapp,
                        'rto' => $verifiedRto,
                        'delivered_reorder' => $verifiedDeliveredReorder,
                        'abandoned' => $verifiedAbandoned,
                    ],

                    'cancelled' => [
                        'count' => $cancelled,
                        'web' => $cancelledWeb,
                        'whatsapp' => $cancelledWhatsapp,
                        'rto' => $cancelledRto,
                        'delivered_reorder' => $cancelledDeliveredReorder,
                        'abandoned' => $cancelledAbandoned,
                    ],

                    'not_connected' => [
                        'count' => $notConnected,
                        'web' => $notConnectedWeb,
                        'whatsapp' => $notConnectedWhatsapp,
                        'rto' => $notConnectedRto,
                        'delivered_reorder' => $notConnectedDeliveredReorder,
                        'abandoned' => $notConnectedAbandoned,
                    ],

                    'same_order' => [
                        'count' => $sameOrder,
                        'web' => $sameOrderWeb,
                        'whatsapp' => $sameOrderWhatsapp,
                        'rto' => $sameOrderRto,
                        'delivered_reorder' => $sameOrderDeliveredReorder,
                        'abandoned' => $sameOrderAbandoned,
                    ],

                    'other' => [
                        'count' => $other,
                        'web' => $otherWeb,
                        'whatsapp' => $otherWhatsapp,
                        'rto' => $otherRto,
                        'delivered_reorder' => $otherDeliveredReorder,
                        'abandoned' => $otherAbandoned,
                    ],
                ],

                'conversion' => [
                    'rate' => $successRate,
                    'verified' => $verified,
                    'total' => $totalOrders,
                ],
            ],
        ]);
    }

    public function outForDelivery(Request $request)
    {
        $orders = $this->getStaffDeliveryOrders(
            $request,
            'Out for Delivery'
        );

        return response()->json([
            'success' => true,
            'message' => 'Out for Delivery orders fetched successfully.',
            'data' => $orders,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
    public function store(Request $request)
    {
        try {

            $data = $request->json()->all();

            if (empty($data)) {
                $data = $request->all();
            }

            Log::info('Knowlarity Log Push Received', [
                'data' => $data
            ]);

            /*
        |--------------------------------------------------------------------------
        | Required Field
        |--------------------------------------------------------------------------
        */

            if (empty($data['call_uuid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'call_uuid is required'
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Check Duplicate Call UUID
        |--------------------------------------------------------------------------
        */

            $existing = DB::table('knowlarity_log')
                ->where('call_uuid', $data['call_uuid'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Call log already exists',
                    'call_uuid' => $existing->call_uuid,
                    'id' => $existing->id
                ], 200);
            }

            /*
        |--------------------------------------------------------------------------
        | Insert Call Log
        |--------------------------------------------------------------------------
        */

            $id = DB::table('knowlarity_log')->insertGetId([
                'call_date' => $data['call_date'] ?? null,
                'call_time' => $data['call_time'] ?? null,
                'caller_number' => $data['caller_number'] ?? null,
                'call_direction' => $data['call_direction'] ?? null,
                'called_number' => $data['called_number'] ?? null,
                'call_status' => $data['call_status'] ?? null,
                'agent_number' => $data['agent_number'] ?? null,
                'call_transfer_status' => $data['call_transfer_status'] ?? null,
                'caller_duration' => $data['caller_duration'] ?? null,
                'recording_url' => $data['recording_url'] ?? null,
                'call_uuid' => $data['call_uuid'],
                'hangup_cause' => $data['hangup_cause'] ?? null,
                'menu_extension' => $data['menu_extension'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => true,
                'message' => 'Call log received successfully',
                'call_uuid' => $data['call_uuid'],
                'id' => $id
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Knowlarity Log Push Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);

            /*
        |--------------------------------------------------------------------------
        | Local Testing Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }



    public function prepaidOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->where('payment_mode', 'paid')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Prepaid orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Web Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function prepaidOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:verified,same_order,not_reachable,cancel'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('payment_mode', 'paid')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Prepaid order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function prepaidOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:paid,COD,Prepaid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Prepaid order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prepaid order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function webOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Web orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Web Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function webOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:verified,same_order,not_reachable,cancel'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Web order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function webOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where(function ($q) {
                $q->whereNull('order_source')
                    ->orWhere('order_source', '');
            })
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Web order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Web order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function rtoOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'RTO')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where(
                'client_id',
                (int) $request->client_id
            );
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'RTO orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'RTO Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function rtoOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'RTO order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'RTO order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function rtoOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'RTO')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'RTO order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RTO order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function deliverOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        // Same client grouping logic as the supplied Blade method.
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        // API-only enhancement: server-side search.
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Delivered reorder orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Deliver Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function deliverOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Deliver order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Deliver order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function deliverOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'deliveredreorder')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Deliver order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deliver order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function abandonedOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $user->id;

        // Same client grouping logic as the supplied Blade method.
        $clients = CallingOrder::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )
            ->where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->groupBy('client_id')
            ->with('client')
            ->get()
            ->map(function ($row) {
                return [
                    'client_id' => $row->client_id,
                    'client_name' => optional($row->client)->client_name ?? 'Client',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $query = CallingOrder::query()
            ->where('assigned_to', $userId)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending');

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        // API-only enhancement: server-side search.
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Abandoned checkout orders fetched successfully.',
            'data' => [
                'orders' => $orders,
                'clients' => $clients,
                'status_label' => 'Abandoned Pending Orders',
                'status_count' => $orders->total(),
            ],
        ]);
    }

    public function abandonedOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'in:verified,same_order,not_reachable,cancel',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Abandoned order not found or not assigned to you.',
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Abandoned order status updated successfully.',
            'data' => $order->fresh(),
        ]);
    }

    public function abandonedOrderUpdate(Request $request, $id)
    {
        $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'digits:10'],
            'product_name' => ['required', 'string', 'max:1000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:COD,Prepaid,paid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'digits:6'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = CallingOrder::where('id', $id)
            ->where('assigned_to', $user->id)
            ->where('order_source', 'shopify_abandoned_checkout')
            ->where('status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' =>
                'Abandoned order not found or not assigned to you.',
            ], 404);
        }

        $order->update([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'product_name' => $request->product_name,
            'father_name' => $request->father_name,
            'quantity' => $request->quantity,
            'payment_mode' => $request->payment_mode,
            'amount' => $request->amount,
            'age' => $request->age,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'shipping_address' => $request->shipping_address,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Abandoned order updated successfully.',
            'data' => $order->fresh(),
        ]);
    }
}
