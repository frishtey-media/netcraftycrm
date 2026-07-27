<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\PaymentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Order;
use App\Exports\PendingPaymentExport;

class PaymentController extends Controller
{

    private function isClient()
    {
        return auth()->check() &&
            auth()->user()->role == 'client';
    }

    private function clientId()
    {
        return auth()->user()?->client_id;
    }

    public function index(Request $request)
    {

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = Payment::query()

            ->leftJoin('orders', function ($join) {

                $join->on('orders.barcode', '=', 'payments.article_number')
                    ->whereIn('orders.payment_mode', [
                        'COD',
                        'cod',
                        'Cash on Delivery'
                    ]);
            })

            ->leftJoin(
                'callingorder',
                'callingorder.order_id',
                '=',
                'orders.order_id'
            )

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Client Login
        |--------------------------------------------------------------------------
        */

        if ($this->isClient()) {

            $query->where(
                'orders.client_id',
                $this->clientId()
            );

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            if ($request->filled('client_id')) {

                $query->where(
                    'orders.client_id',
                    $request->client_id
                );
            }
        }



        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from')) {

            $query->whereDate(
                'payments.delivered_date',
                '>=',
                Carbon::parse($request->from)
            );
        }

        if ($request->filled('to')) {

            $query->whereDate(
                'payments.delivered_date',
                '<=',
                Carbon::parse($request->to)
            );
        }


        /*
|--------------------------------------------------------------------------
| Order Source Filter
|--------------------------------------------------------------------------
*/

        if ($request->filled('order_source')) {

            if ($request->order_source == 'web') {

                $query->whereNull('callingorder.order_source');
            } elseif ($request->order_source == 'whatsapp') {

                $query->where('callingorder.order_source', 'whatsapp');
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Product Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('product')) {

            $query->where(
                'orders.product',
                $request->product
            );
        }



        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'payments.article_number',
                    'like',
                    "%{$request->search}%"
                )

                    ->orWhere(
                        'orders.order_id',
                        'like',
                        "%{$request->search}%"
                    )

                    ->orWhere(
                        'orders.customer_name',
                        'like',
                        "%{$request->search}%"
                    )

                    ->orWhere(
                        'orders.customer_phone',
                        'like',
                        "%{$request->search}%"
                    );
            });
        }



        /*
        |--------------------------------------------------------------------------
        | Dashboard Cards
        |--------------------------------------------------------------------------
        */

        $totalArticles = (clone $query)
            ->distinct('payments.id')
            ->count('payments.id');

        $totalAmount = (clone $query)
            ->sum('payments.cod_value');


        $matchedArticles = (clone $query)

            ->whereNotNull('orders.id')

            ->distinct('payments.id')

            ->count('payments.id');

        $unMatchedArticles = (clone $query)

            ->whereNull('orders.id')

            ->distinct('payments.id')

            ->count('payments.id');



        /*
        |--------------------------------------------------------------------------
        | Client Wise Summary
        |--------------------------------------------------------------------------
        */
        $clientSummary = (clone $query)

            ->whereNotNull('orders.id')

            ->select(

                'clients.id',

                'clients.client_name',

                DB::raw('COUNT(DISTINCT payments.id) as articles'),

                DB::raw('SUM(payments.cod_value) as amount')

            )

            ->groupBy(
                'clients.id',
                'clients.client_name'
            )

            ->orderByDesc('amount')

            ->get();



        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = (clone $query)

            ->whereNotNull('orders.product')

            ->select('orders.product')

            ->distinct()

            ->orderBy('orders.product')

            ->pluck('product');



        /*
|--------------------------------------------------------------------------
| Pending Payment
|--------------------------------------------------------------------------
*/

        $pendingQuery = Order::query()
            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->whereIn('orders.payment_mode', [
                'COD',
                'cod',
                'Cash on Delivery'
            ]);

        // Client
        if ($this->isClient()) {

            $pendingQuery->where(
                'orders.client_id',
                $this->clientId()
            );
        } elseif ($request->filled('client_id')) {

            $pendingQuery->where(
                'orders.client_id',
                $request->client_id
            );
        }

        // Product
        if ($request->filled('product')) {

            $pendingQuery->where(
                'orders.product',
                $request->product
            );
        }

        // Date (use Delivery Date)
        if ($request->filled('from')) {

            $pendingQuery->whereDate(
                'orders.delivery_date',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {

            $pendingQuery->whereDate(
                'orders.delivery_date',
                '<=',
                $request->to
            );
        }

        // Search
        if ($request->filled('search')) {

            $search = trim($request->search);

            $pendingQuery->where(function ($q) use ($search) {

                $q->where(
                    'orders.order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'orders.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_phone',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        // Order Source
        if ($request->filled('order_source')) {

            if ($request->order_source == 'web') {

                $pendingQuery->whereNull('orders.order_source');
            } else {

                $pendingQuery->where(
                    'orders.order_source',
                    'whatsapp'
                );
            }
        }

        // Pending Payment Condition
        $pendingQuery->where(
            'orders.delivery_status',
            'Delivered'
        )

            ->where(function ($q) {

                $q->whereNull('orders.recivedpaysts')
                    ->orWhere('orders.recivedpaysts', 0);
            });

        $pendingPaymentOrders = (clone $pendingQuery)->count();

        $pendingPaymentAmount = (clone $pendingQuery)->sum('orders.amount');


        /*
        |--------------------------------------------------------------------------
        | Unmatched Articles
        |--------------------------------------------------------------------------
        */

        $unmatched = (clone $query)

            ->whereNull('orders.id')

            ->select(

                'payments.article_number',

                'payments.cod_invoice_number',

                'payments.cod_value',

                'payments.bill_date',

                'payments.customer_name'

            )

            ->latest('payments.bill_date')

            ->get();

        $productSummary = (clone $query)

            ->whereNotNull('orders.product')

            ->select(

                'orders.product',

                DB::raw('COUNT(DISTINCT payments.id) as articles'),

                DB::raw('SUM(payments.cod_value) as amount')

            )

            ->groupBy('orders.product')

            ->orderByDesc('amount')

            ->get();
        $dateSummary = (clone $query)

            ->select(

                'payments.bill_date',

                DB::raw('COUNT(DISTINCT payments.id) as articles'),

                DB::raw('SUM(payments.cod_value) as amount')

            )

            ->groupBy('payments.bill_date')

            ->orderByDesc('payments.bill_date')

            ->get();

        /*
|--------------------------------------------------------------------------
| Web & WhatsApp Counting
|--------------------------------------------------------------------------
*/

        $webArticles = (clone $query)

            ->where(function ($q) {

                $q->whereNull('callingorder.order_source')
                    ->orWhere('callingorder.order_source', '');
            })

            ->distinct('payments.id')

            ->count('payments.id');

        $whatsappArticles = (clone $query)

            ->where('callingorder.order_source', 'whatsapp')

            ->distinct('payments.id')

            ->count('payments.id');

        $webAmount = (clone $query)

            ->where(function ($q) {

                $q->whereNull('callingorder.order_source')
                    ->orWhere('callingorder.order_source', '');
            })

            ->sum('payments.cod_value');
        $whatsappAmount = (clone $query)

            ->where('callingorder.order_source', 'whatsapp')

            ->sum('payments.cod_value');


        $payments = (clone $query)

            ->whereNotNull('orders.id')

            ->select(

                'payments.id',

                'payments.article_number',

                'payments.cod_invoice_number',

                'payments.bill_date',

                'payments.delivered_date',

                'payments.cod_value',

                'payments.cod_commission',

                'orders.order_id',

                'orders.customer_name',

                'orders.customer_phone',

                'orders.shipping_address',

                'orders.city',

                'orders.state',

                'orders.pincode',

                'orders.product',

                'orders.quantity',

                'orders.weight',

                'orders.amount',

                'clients.client_name'

            )

            ->distinct('payments.id')

            ->latest('payments.bill_date')

            ->paginate($request->records ?? 100);
        return view(
            'payments.index',
            compact(

                'payments',

                'clients',

                'products',
                'pendingPaymentOrders',
                'pendingPaymentAmount',
                'webArticles',
                'whatsappArticles',
                'webAmount',
                'whatsappAmount',

                'clientSummary',

                'productSummary',

                'dateSummary',

                'totalArticles',

                'matchedArticles',

                'unMatchedArticles',

                'totalAmount',
                'payments',

                'unmatched'

            )
        );
    }
    public function pendingPayment(Request $request)
    {
        $query = Order::query()

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->whereIn('orders.payment_mode', [
                'COD',
                'cod',
                'Cash on Delivery'
            ]);
        /*
    |--------------------------------------------------------------------------
    | Client Filter
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $query->where(
                'orders.client_id',
                $this->clientId()
            );

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();

            if ($request->filled('client_id')) {

                $query->where(
                    'orders.client_id',
                    $request->client_id
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Pending Payment
    |--------------------------------------------------------------------------
    */

        $query->where(
            'orders.delivery_status',
            'Delivered'
        )

            ->where(function ($q) {

                $q->whereNull('orders.recivedpaysts')

                    ->orWhere(
                        'orders.recivedpaysts',
                        0
                    );
            });

        /*
    |--------------------------------------------------------------------------
    | Date Filter
    |--------------------------------------------------------------------------
    */

        if ($request->filled('from')) {

            $query->whereDate(
                'orders.delivery_date',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {

            $query->whereDate(
                'orders.delivery_date',
                '<=',
                $request->to
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

        if ($request->filled('product')) {

            $query->where(
                'orders.product',
                $request->product
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Web / WhatsApp
    |--------------------------------------------------------------------------
    */

        if ($request->filled('order_source')) {

            if ($request->order_source == 'web') {

                $query->whereNull(
                    'orders.order_source'
                );
            } else {

                $query->where(
                    'orders.order_source',
                    'whatsapp'
                );
            }
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
                    'orders.order_id',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'orders.barcode',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'orders.customer_phone',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Dashboard Cards
    |--------------------------------------------------------------------------
    */

        $totalOrders = (clone $query)->count();

        $totalAmount = (clone $query)->sum(
            'orders.amount'
        );

        /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

        $products = Order::select('product')

            ->whereNotNull('product')

            ->distinct()

            ->orderBy('product')

            ->pluck('product');

        /*
    |--------------------------------------------------------------------------
    | Data Table
    |--------------------------------------------------------------------------
    */

        $orders = (clone $query)

            ->select(

                'orders.id',

                'orders.order_id',

                'orders.barcode',

                'orders.customer_name',

                'orders.customer_phone',

                'orders.shipping_address',

                'orders.city',

                'orders.state',

                'orders.pincode',

                'orders.product',

                'orders.quantity',

                'orders.amount',

                'orders.delivery_date',

                'orders.delivery_status',

                'clients.client_name'

            )

            ->latest('orders.delivery_date')

            ->paginate(
                $request->records ?? 100
            );

        return view(

            'payments.pending',

            compact(

                'orders',

                'clients',

                'products',

                'totalOrders',

                'totalAmount'

            )

        );
    }
    public function pendingPaymentExport(Request $request)
    {
        return Excel::download(

            new PendingPaymentExport($request),

            'Pending_Payment_' .
                now()->format('YmdHis') .
                '.xlsx'

        );
    }
    public function export(Request $request)
    {
        return Excel::download(
            new PaymentExport($request),
            'Payment_Report_' . date('YmdHis') . '.xlsx'
        );
    }
}
