<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepeatCustomerController extends Controller
{

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

    public function repeatRto(Request $request)
    {
        $query = DB::table('orders')

            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')

            ->leftJoin('callingorder', 'callingorder.order_id', '=', 'orders.order_id')

            ->leftJoin('calling_users', 'calling_users.id', '=', 'callingorder.assigned_to');

        /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

        if ($request->filled('client')) {
            $query->where('orders.client_id', $request->client);
        }

        if ($request->filled('staff')) {
            $query->where('callingorder.assigned_to', $request->staff);
        }

        if ($request->filled('phone')) {
            $query->where(
                'orders.customer_phone',
                'like',
                '%' . trim($request->phone) . '%'
            );
        }

        if ($request->filled('product')) {
            $query->where(
                'orders.product',
                $request->product
            );
        }

        if ($request->filled('from')) {
            $query->whereDate(
                'orders.created_at',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                'orders.created_at',
                '<=',
                $request->to
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Repeat Customer Summary
    |--------------------------------------------------------------------------
    */

        $customers = $query

            ->select(

                'orders.customer_phone',

                'orders.client_id',

                DB::raw("MAX(orders.customer_name) as customer_name"),

                DB::raw("MAX(clients.client_name) as client_name"),

                DB::raw("MAX(orders.shipping_address) as shipping_address"),

                DB::raw("MAX(orders.city) as city"),

                DB::raw("COUNT(*) as total_orders"),

                DB::raw("SUM(orders.delivery_status='Delivered') as delivered"),

                DB::raw("SUM(orders.delivery_status='RTO') as total_rto"),

                DB::raw("SUM(
                CASE
                    WHEN orders.delivery_status='In Transit'
                    OR orders.delivery_status='Out For Delivery'
                    THEN 1
                    ELSE 0
                END
            ) as transit"),

                DB::raw("MAX(orders.created_at) as last_order_date"),

                DB::raw("
                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        orders.delivery_status
                        ORDER BY orders.created_at DESC
                    ),
                    ',',
                    1
                ) as last_status
            "),

                DB::raw("
                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                        IFNULL(calling_users.name,'-')
                        ORDER BY orders.created_at DESC
                    ),
                    ',',
                    1
                ) as last_staff
            ")

            )

            ->groupBy(

                'orders.customer_phone',

                'orders.client_id'

            )

            ->havingRaw("COUNT(*) >= 2")

            ->orderByDesc('total_orders')

            ->paginate(50)

            ->withQueryString();

        /*
    |--------------------------------------------------------------------------
    | Filters Data
    |--------------------------------------------------------------------------
    */

        $clients = DB::table('clients')
            ->orderBy('client_name')
            ->get();

        $staff = DB::table('calling_users')
            ->orderBy('name')
            ->get();

        $products = DB::table('orders')
            ->select('product')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');

        return view(
            'reports.repeat-rto.index',
            compact(
                'customers',
                'clients',
                'staff',
                'products'
            )
        );
    }
    public function repeatRtoDetail(Request $request, $phone)
    {

        $orders = DB::table('orders')

            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')

            ->leftJoin(DB::raw("
(
    SELECT order_id,
           MAX(assigned_to) assigned_to,
           MAX(order_source) order_source
    FROM callingorder
    GROUP BY order_id
) callingorder
"), 'orders.order_id', '=', 'callingorder.order_id')

            ->leftJoin('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')

            ->select(

                'orders.*',

                'clients.client_name',

                'calling_users.name as staff_name',

                'callingorder.order_source'

            )

            ->where('orders.customer_phone', $phone)
            //   ->where('orders.shipping_address', $address)

            ->orderBy('orders.created_at', 'asc')

            ->get();
        $recoveryChain = [];

        $attempt = 1;

        foreach ($orders as $order) {

            $recoveryChain[] = [

                'attempt' => $attempt,

                'order_id' => $order->order_id,

                'barcode' => $order->barcode,

                'date' => $order->created_at,

                'product' => $order->product,

                'staff' => $order->staff_name,

                'source' => $order->order_source,

                'status' => $order->delivery_status,

                'amount' => $order->amount

            ];

            $attempt++;
        }
        if ($orders->isEmpty()) {
            abort(404);
        }

        $customer = $orders->first();
        $summary = [

            'total_orders' => $orders->count(),

            'delivered' => $orders->where('delivery_status', 'Delivered')->count(),

            'rto' => $orders->where('delivery_status', 'RTO')->count(),

            'total_amount' => $orders->sum('amount'),

        ];
        $productSummary = DB::table('orders')

            ->select(

                'product',

                DB::raw('COUNT(*) as orders'),

                DB::raw("SUM(CASE WHEN delivery_status='Delivered' THEN 1 ELSE 0 END) as delivered"),

                DB::raw("SUM(CASE WHEN delivery_status='RTO' THEN 1 ELSE 0 END) as rto"),

                DB::raw("SUM(amount) as amount")

            )

            ->where('customer_phone', $phone)

            ->groupBy('product')

            ->get();
        $staffSummary = DB::table('orders')

            ->leftJoin('callingorder', 'orders.order_id', '=', 'callingorder.order_id')

            ->leftJoin('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')

            ->select(

                'calling_users.name',

                DB::raw('COUNT(*) as total_orders'),

                DB::raw("SUM(CASE WHEN orders.delivery_status='Delivered' THEN 1 ELSE 0 END) as delivered"),

                DB::raw("SUM(CASE WHEN orders.delivery_status='RTO' THEN 1 ELSE 0 END) as rto")

            )

            ->where('orders.customer_phone', $phone)
            //->where('orders.shipping_address', $address)

            ->groupBy('calling_users.name')

            ->get();
        $sourceSummary = DB::table('orders')

            ->leftJoin('callingorder', 'orders.order_id', '=', 'callingorder.order_id')

            ->select(

                'callingorder.order_source',

                DB::raw('COUNT(*) as total'),

                DB::raw("SUM(CASE WHEN orders.delivery_status='Delivered' THEN 1 ELSE 0 END) as delivered"),

                DB::raw("SUM(CASE WHEN orders.delivery_status='RTO' THEN 1 ELSE 0 END) as rto")

            )

            ->where('orders.customer_phone', $phone)
            // ->where('orders.shipping_address', $address)

            ->groupBy('callingorder.order_source')

            ->get();
        $timeline = [];

        foreach ($orders as $order) {

            $timeline[] = [

                'date' => $order->created_at,

                'status' => $order->delivery_status,

                'staff' => $order->staff_name,

                'product' => $order->product,

                'barcode' => $order->barcode,

            ];
        }

        $finalStatus = $orders->last()->delivery_status;

        $firstRto = $orders->where('delivery_status', 'RTO')->first();

        $totalAttempts = $orders->count();

        $successfulAttempt = null;

        foreach ($orders as $index => $order) {

            if ($order->delivery_status == "Delivered") {

                $successfulAttempt = $index + 1;

                break;
            }
        }
        $staffRecovery = DB::table('orders')

            ->leftJoin('callingorder', 'orders.order_id', '=', 'callingorder.order_id')

            ->leftJoin('calling_users', 'callingorder.assigned_to', '=', 'calling_users.id')

            ->select(

                'calling_users.name',

                DB::raw("COUNT(*) total"),

                DB::raw("SUM(CASE WHEN orders.delivery_status='Delivered' THEN 1 ELSE 0 END) delivered"),

                DB::raw("SUM(CASE WHEN orders.delivery_status='RTO' THEN 1 ELSE 0 END) rto"),

                DB::raw("SUM(orders.amount) revenue")

            )

            ->where('orders.customer_phone', $phone)
            // ->where('orders.shipping_address', $address)

            ->groupBy('calling_users.name')

            ->get();
        return view(

            'reports.repeat-rto.detail',

            compact(
                'recoveryChain',
                'customer',

                'orders',
                'finalStatus',

                'totalAttempts',

                'successfulAttempt',
                'summary',

                'productSummary',

                'staffSummary',

                'sourceSummary',

                'staffRecovery',
                'timeline'

            )

        );
    }

    public function customerHistory(Request $request)
    {
        $phone = $this->normalizePhone($request->phone);

        if (!$phone) {
            return response()->json([
                'orders' => [],
                'delivered_order' => null,
                'phone' => null,
            ]);
        }

        $last10 = strlen($phone) >= 10
            ? substr($phone, -10)
            : $phone;

        /*
    |--------------------------------------------------------------------------
    | Previous Calling Order History
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

            ->where(function ($query) use ($phone, $last10) {

                $cleanPhone = "
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

                $query->whereRaw("{$cleanPhone} = ?", [$phone]);

                if (strlen($last10) === 10) {
                    $query->orWhereRaw(
                        "RIGHT({$cleanPhone},10)=?",
                        [$last10]
                    );
                }
            })

            ->select([

                'callingorder.id',

                'callingorder.order_id',

                'callingorder.barcode',

                'callingorder.client_id',

                DB::raw('callingorder.product_name as product'),

                'callingorder.quantity',

                'callingorder.weight',

                'callingorder.amount',

                'callingorder.payment_mode',

                'callingorder.customer_name',

                'callingorder.father_name',

                'callingorder.age',

                'callingorder.customer_phone',

                'callingorder.shipping_address',

                'callingorder.city',

                'callingorder.state',

                'callingorder.pincode',

                DB::raw('callingorder.status as delivery_status'),

                'callingorder.created_at',

                'clients.client_name',

                DB::raw('calling_users.name as staff_name')

            ])

            ->orderByDesc('callingorder.created_at')

            ->get();


        /*
    |--------------------------------------------------------------------------
    | Latest Delivered Order
    |--------------------------------------------------------------------------
    */

        $deliveredOrder = $orders
            ->filter(function ($order) {

                return strtolower(trim($order->delivery_status ?? '')) == 'delivered';
            })
            ->first();


        return response()->json([

            'orders' => $orders,

            'delivered_order' => $deliveredOrder,

            'phone' => $phone,

        ]);
    }
}
