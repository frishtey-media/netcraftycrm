<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\PaymentExport;
use Maatwebsite\Excel\Facades\Excel;

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

            ->leftJoin(
                'orders',
                'orders.barcode',
                '=',
                'payments.article_number'
            )

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
                'payments.bill_date',
                '>=',
                Carbon::parse($request->from)
            );
        }

        if ($request->filled('to')) {

            $query->whereDate(
                'payments.bill_date',
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

        $totalArticles = (clone $query)->count();

        $totalAmount = (clone $query)
            ->sum('payments.cod_value');

        $matchedArticles = (clone $query)

            ->whereNotNull('orders.id')

            ->count();

        $unMatchedArticles = (clone $query)

            ->whereNull('orders.id')

            ->count();



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

                DB::raw('COUNT(payments.id) as articles'),

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
        | Matched Orders
        |--------------------------------------------------------------------------
        */

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

            ->latest('payments.bill_date')

            ->paginate(
                $request->records ?? 100
            );



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

                DB::raw('COUNT(payments.id) as articles'),

                DB::raw('SUM(payments.cod_value) as amount')

            )

            ->groupBy('orders.product')

            ->orderByDesc('amount')

            ->get();
        $dateSummary = (clone $query)

            ->select(

                'payments.bill_date',

                DB::raw('COUNT(payments.id) as articles'),

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
            ->whereNull('callingorder.order_source')
            ->count();

        $whatsappArticles = (clone $query)
            ->where('callingorder.order_source', 'whatsapp')
            ->count();

        $webAmount = (clone $query)
            ->whereNull('callingorder.order_source')
            ->sum('payments.cod_value');

        $whatsappAmount = (clone $query)
            ->where('callingorder.order_source', 'whatsapp')
            ->sum('payments.cod_value');

        return view(
            'payments.index',
            compact(

                'payments',

                'clients',

                'products',

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

                'unmatched'

            )
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
