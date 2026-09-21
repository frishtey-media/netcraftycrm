<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentExport;
use App\Exports\PendingPaymentExport;

class PaymentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CLIENT HELPERS
    |--------------------------------------------------------------------------
    */

    private function isClient()
    {
        return auth()->check()
            && auth()->user()->role == 'client';
    }

    private function clientId()
    {
        return auth()->user()?->client_id;
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT REPORT
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = Payment::query()

            ->leftJoin('orders', function ($join) {

                $join->on(
                    DB::raw('TRIM(UPPER(orders.barcode))'),
                    '=',
                    DB::raw(
                        'TRIM(UPPER(payments.article_number))'
                    )
                )

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
        | CLIENT
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
| DELIVERY DATE FILTER
|--------------------------------------------------------------------------
| IMPORTANT:
| Report is based on when the ORDER was delivered.
| Payment may be received next day or later.
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
        | ORDER SOURCE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('order_source')) {

            if (
                $request->order_source === 'web'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'callingorder.order_source'
                    )

                        ->orWhere(
                            'callingorder.order_source',
                            ''
                        );
                });
            } elseif (
                $request->order_source === 'whatsapp'
            ) {

                $query->where(
                    'callingorder.order_source',
                    'whatsapp'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
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
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'payments.article_number',
                    'like',
                    "%{$search}%"
                )

                    ->orWhere(
                        'orders.order_id',
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
        | DASHBOARD
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
        | CLIENT SUMMARY
        |--------------------------------------------------------------------------
        */

        $clientSummary = (clone $query)

            ->whereNotNull('orders.id')

            ->select(
                'clients.id',
                'clients.client_name',

                DB::raw(
                    'COUNT(DISTINCT payments.id) as articles'
                ),

                DB::raw(
                    'SUM(payments.cod_value) as amount'
                )
            )

            ->groupBy(
                'clients.id',
                'clients.client_name'
            )

            ->orderByDesc('amount')

            ->get();

        $products = (clone $query)

            ->whereNotNull(
                'orders.product'
            )

            ->select(
                'orders.product'
            )

            ->distinct()

            ->orderBy(
                'orders.product'
            )

            ->pluck(
                'product'
            );

        $pendingQuery = Order::query()

            ->whereIn(
                'orders.payment_mode',
                [
                    'COD',
                    'cod',
                    'Cash on Delivery'
                ]
            );


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


        if ($request->filled('product')) {

            $pendingQuery->where(
                'orders.product',
                $request->product
            );
        }

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

        if ($request->filled('order_source')) {

            if (
                $request->order_source === 'web'
            ) {

                $pendingQuery->where(function ($q) {

                    $q->whereNull(
                        'orders.order_source'
                    )

                        ->orWhere(
                            'orders.order_source',
                            ''
                        );
                });
            } elseif (
                $request->order_source === 'whatsapp'
            ) {

                $pendingQuery->where(
                    'orders.order_source',
                    'whatsapp'
                );
            }
        }

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

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

        $pendingQuery

            ->where(
                'orders.delivery_status',
                'Delivered'
            )

            ->where(function ($q) {

                $q->whereNull(
                    'orders.recivedpaysts'
                )

                    ->orWhere(
                        'orders.recivedpaysts',
                        0
                    );
            });


        $pendingPaymentOrders =
            (clone $pendingQuery)->count();


        $pendingPaymentAmount =
            (clone $pendingQuery)
            ->sum('orders.amount');

        $unmatched = (clone $query)

            ->whereNull(
                'orders.id'
            )

            ->select(
                'payments.article_number',
                'payments.cod_invoice_number',
                'payments.cod_value',
                'payments.bill_date',
                'payments.customer_name'
            )

            ->latest(
                'payments.bill_date'
            )

            ->get();

        $productSummary = (clone $query)

            ->whereNotNull(
                'orders.product'
            )

            ->select(
                'orders.product',

                DB::raw(
                    'COUNT(DISTINCT payments.id) as articles'
                ),

                DB::raw(
                    'SUM(payments.cod_value) as amount'
                )
            )

            ->groupBy(
                'orders.product'
            )

            ->orderByDesc(
                'amount'
            )

            ->get();

        $dateSummary = (clone $query)

            ->select(
                'payments.bill_date',

                DB::raw(
                    'COUNT(DISTINCT payments.id) as articles'
                ),

                DB::raw(
                    'SUM(payments.cod_value) as amount'
                )
            )

            ->groupBy(
                'payments.bill_date'
            )

            ->orderByDesc(
                'payments.bill_date'
            )

            ->get();

        $webArticles = (clone $query)

            ->where(function ($q) {

                $q->whereNull(
                    'callingorder.order_source'
                )

                    ->orWhere(
                        'callingorder.order_source',
                        ''
                    );
            })

            ->distinct(
                'payments.id'
            )

            ->count(
                'payments.id'
            );


        $whatsappArticles = (clone $query)

            ->where(
                'callingorder.order_source',
                'whatsapp'
            )

            ->distinct(
                'payments.id'
            )

            ->count(
                'payments.id'
            );


        $webAmount = (clone $query)

            ->where(function ($q) {

                $q->whereNull(
                    'callingorder.order_source'
                )

                    ->orWhere(
                        'callingorder.order_source',
                        ''
                    );
            })

            ->sum(
                'payments.cod_value'
            );


        $whatsappAmount = (clone $query)

            ->where(
                'callingorder.order_source',
                'whatsapp'
            )

            ->sum(
                'payments.cod_value'
            );

        $payments = (clone $query)

            ->whereNotNull(
                'orders.id'
            )

            ->select(

                'payments.id',

                'payments.article_number',

                'payments.cod_invoice_number',

                'payments.bill_date',

                'orders.delivery_date',

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

            ->distinct(
                'payments.id'
            )

            ->latest(
                'payments.bill_date'
            )

            ->paginate(
                $request->records ?? 100
            );


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

                'unmatched'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT EXCEL UPLOAD
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Payment date != Delivery date.
    |
    | Payment matches by Article Number / Barcode only.
    |
    */

    public function paymentupload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:51200'
            ]
        ]);


        try {

            $sheets = Excel::toArray(
                [],
                $request->file('file')
            );


            if (
                empty($sheets) ||
                empty($sheets[0])
            ) {

                return back()->with(
                    'error',
                    'Payment Excel is empty.'
                );
            }


            $rows = $sheets[0];


            /*
            |--------------------------------------------------------------------------
            | HEADER
            |--------------------------------------------------------------------------
            */

            $headers = array_shift(
                $rows
            );


            $headers = array_map(
                function ($header) {

                    return $this->normalizeHeader(
                        $header
                    );
                },
                $headers
            );


            $total = 0;
            $matched = 0;
            $received = 0;
            $alreadyReceived = 0;
            $unmatched = 0;
            $skipped = 0;


            DB::beginTransaction();


            foreach ($rows as $row) {

                $total++;


                /*
                |--------------------------------------------------------------------------
                | CREATE ASSOCIATIVE DATA
                |--------------------------------------------------------------------------
                */

                $data = [];

                foreach (
                    $headers as $index => $header
                ) {

                    if (
                        $header !== ''
                    ) {

                        $data[$header] =
                            $row[$index] ?? null;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | ARTICLE NUMBER
                |--------------------------------------------------------------------------
                */

                $articleNumber =
                    $this->firstValue(
                        $data,
                        [
                            'article_number',
                            'article_no',
                            'article',
                            'barcode',
                            'tracking_number',
                            'tracking_no',
                            'consignment_number',
                            'consignment_no',
                            'item_number'
                        ]
                    );


                if (!$articleNumber) {

                    $skipped++;

                    continue;
                }


                $articleNumber =
                    $this->normalizeBarcode(
                        $articleNumber
                    );


                /*
                |--------------------------------------------------------------------------
                | INVOICE
                |--------------------------------------------------------------------------
                */

                $invoiceNumber =
                    $this->firstValue(
                        $data,
                        [
                            'cod_invoice_number',
                            'cod_invoice_no',
                            'invoice_number',
                            'invoice_no',
                            'invoice'
                        ]
                    );


                /*
                |--------------------------------------------------------------------------
                | BILL DATE
                |--------------------------------------------------------------------------
                */

                $billDate =
                    $this->firstValue(
                        $data,
                        [
                            'bill_date',
                            'payment_date',
                            'billdate',
                            'date'
                        ]
                    );


                $billDate =
                    $this->parseDate(
                        $billDate
                    );


                /*
                |--------------------------------------------------------------------------
                | COD VALUE
                |--------------------------------------------------------------------------
                */

                $codValue =
                    $this->firstValue(
                        $data,
                        [
                            'cod_value',
                            'cod_amount',
                            'amount',
                            'cod',
                            'cash_on_delivery'
                        ]
                    );


                $codValue =
                    $this->parseAmount(
                        $codValue
                    );


                /*
                |--------------------------------------------------------------------------
                | COMMISSION
                |--------------------------------------------------------------------------
                */

                $codCommission =
                    $this->firstValue(
                        $data,
                        [
                            'cod_commission',
                            'commission',
                            'cod_charges'
                        ]
                    );


                $codCommission =
                    $this->parseAmount(
                        $codCommission
                    );


                /*
                |--------------------------------------------------------------------------
                | FIND ORDER
                |--------------------------------------------------------------------------
                */

                $order = Order::whereRaw(
                    'TRIM(UPPER(barcode)) = ?',
                    [$articleNumber]
                )

                    ->whereIn(
                        'payment_mode',
                        [
                            'COD',
                            'cod',
                            'Cash on Delivery'
                        ]
                    )

                    ->latest('id')

                    ->first();


                /*
                |--------------------------------------------------------------------------
                | CUSTOMER
                |--------------------------------------------------------------------------
                */

                $customerName =
                    $this->firstValue(
                        $data,
                        [
                            'customer_name',
                            'customer',
                            'name'
                        ]
                    );


                if (
                    !$customerName &&
                    $order
                ) {

                    $customerName =
                        $order->customer_name;
                }


                /*
                |--------------------------------------------------------------------------
                | DELIVERY DATE
                |--------------------------------------------------------------------------
                |
                | ALWAYS TAKE FROM ORDERS
                |
                */

                $deliveredDate = null;

                if ($order) {

                    $deliveredDate =
                        $order->delivery_date;
                }


                /*
                |--------------------------------------------------------------------------
                | SAVE PAYMENT
                |--------------------------------------------------------------------------
                */

                $payment = null;


                /*
                | First try Article + Invoice
                */

                if ($invoiceNumber) {

                    $payment =
                        Payment::whereRaw(
                            'TRIM(UPPER(article_number)) = ?',
                            [$articleNumber]
                        )

                        ->where(
                            'cod_invoice_number',
                            $invoiceNumber
                        )

                        ->first();
                }


                /*
                | If no invoice, find by article
                */

                if (!$payment) {

                    $payment =
                        Payment::whereRaw(
                            'TRIM(UPPER(article_number)) = ?',
                            [$articleNumber]
                        )

                        ->latest('id')

                        ->first();
                }


                $paymentData = [

                    'article_number' =>
                    $articleNumber,

                    'cod_invoice_number' =>
                    $invoiceNumber,

                    'bill_date' =>
                    $billDate,

                    'delivered_date' =>
                    $deliveredDate,

                    'cod_value' =>
                    $codValue,

                    'cod_commission' =>
                    $codCommission,

                    'customer_name' =>
                    $customerName,
                ];


                if ($payment) {

                    $payment->update(
                        $paymentData
                    );
                } else {

                    $payment =
                        Payment::create(
                            $paymentData
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | ORDER FOUND
                |--------------------------------------------------------------------------
                */

                if (!$order) {

                    $unmatched++;

                    continue;
                }


                $matched++;


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT PAYMENT SYNC
                |--------------------------------------------------------------------------
                |
                | Payment received can only close pending
                | payment when order is Delivered.
                |
                */

                if (
                    strtolower(
                        trim(
                            (string)
                            $order->delivery_status
                        )
                    ) === 'delivered'
                ) {

                    $wasReceived =
                        (int)
                        $order->recivedpaysts === 1;


                    $orderUpdate = [

                        'recivedpaysts' =>
                        1,

                        'receivedcodamt' =>
                        $codValue,
                    ];


                    if ($billDate) {

                        $orderUpdate['pay_bill_date'] = $billDate;
                    }


                    $order->update(
                        $orderUpdate
                    );


                    if ($wasReceived) {

                        $alreadyReceived++;
                    } else {

                        $received++;
                    }
                }
            }


            DB::commit();


            return back()->with(
                'payment_success',

                "Payment upload completed. " .

                    "Total: {$total}, " .

                    "Matched: {$matched}, " .

                    "Payment Received: {$received}, " .

                    "Already Received: {$alreadyReceived}, " .

                    "Unmatched: {$unmatched}, " .

                    "Skipped: {$skipped}"
            );
        } catch (\Throwable $e) {

            DB::rollBack();


            Log::error(
                'Payment Upload Error',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString()
                ]
            );


            return back()->with(
                'error',
                'Payment upload failed: ' .
                    $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERY EXCEL UPLOAD
    |--------------------------------------------------------------------------
    |
    | Delivery upload will also check existing payment.
    |
    | This is what makes the system 100% sync.
    |
    */

    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:51200'
            ]
        ]);


        try {

            $sheets = Excel::toArray(
                [],
                $request->file('file')
            );


            if (
                empty($sheets) ||
                empty($sheets[0])
            ) {

                return back()->with(
                    'error',
                    'Delivery Excel is empty.'
                );
            }


            $rows = $sheets[0];


            $headers = array_shift(
                $rows
            );


            $headers = array_map(
                function ($header) {

                    return $this->normalizeHeader(
                        $header
                    );
                },
                $headers
            );


            $total = 0;
            $matched = 0;
            $updated = 0;
            $paymentAutoMatched = 0;
            $notFound = 0;
            $skipped = 0;


            DB::beginTransaction();


            foreach ($rows as $row) {

                $total++;


                $data = [];


                foreach (
                    $headers as $index => $header
                ) {

                    if (
                        $header !== ''
                    ) {

                        $data[$header] =
                            $row[$index] ?? null;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | BARCODE
                |--------------------------------------------------------------------------
                */

                $barcode =
                    $this->firstValue(
                        $data,
                        [
                            'article_number',
                            'article_no',
                            'article',
                            'barcode',
                            'tracking_number',
                            'tracking_no',
                            'consignment_number',
                            'consignment_no',
                            'item_number'
                        ]
                    );


                if (!$barcode) {

                    $skipped++;

                    continue;
                }


                $barcode =
                    $this->normalizeBarcode(
                        $barcode
                    );


                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                $rawStatus =
                    $this->firstValue(
                        $data,
                        [
                            'delivery_status',
                            'status',
                            'article_status',
                            'event',
                            'remarks',
                            'last_event'
                        ]
                    );


                $status =
                    $this->normalizeStatus(
                        $rawStatus
                    );


                /*
                |--------------------------------------------------------------------------
                | DELIVERY DATE
                |--------------------------------------------------------------------------
                */

                $deliveryDate =
                    $this->firstValue(
                        $data,
                        [
                            'delivery_date',
                            'delivered_date',
                            'delivery_datetime',
                            'event_date',
                            'date'
                        ]
                    );


                $deliveryDate =
                    $this->parseDate(
                        $deliveryDate
                    );


                /*
                |--------------------------------------------------------------------------
                | FIND ORDER
                |--------------------------------------------------------------------------
                */

                $order = Order::whereRaw(
                    'TRIM(UPPER(barcode)) = ?',
                    [$barcode]
                )

                    ->latest('id')

                    ->first();


                if (!$order) {

                    $notFound++;

                    continue;
                }


                $matched++;


                /*
                |--------------------------------------------------------------------------
                | UPDATE DATA
                |--------------------------------------------------------------------------
                */

                $update = [];


                if ($status) {

                    $update['delivery_status'] = $status;
                }


                /*
                |--------------------------------------------------------------------------
                | ONLY SET DELIVERY DATE WHEN AVAILABLE
                |--------------------------------------------------------------------------
                */

                if ($deliveryDate) {

                    $update['delivery_date'] = $deliveryDate;
                }


                /*
                |--------------------------------------------------------------------------
                | DELIVERED
                |--------------------------------------------------------------------------
                */

                if (
                    $this->isDeliveredStatus(
                        $status
                    )
                ) {

                    $update['delivery_status'] = 'Delivered';


                    /*
                    | Don't reset payment received.
                    */

                    if (
                        is_null(
                            $order->recivedpaysts
                        )
                    ) {

                        $update['recivedpaysts'] = 0;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | SAVE ORDER
                |--------------------------------------------------------------------------
                */

                if (!empty($update)) {

                    $order->update(
                        $update
                    );

                    $updated++;
                }


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                |
                | DELIVERY -> CHECK EXISTING PAYMENT
                |--------------------------------------------------------------------------
                |
                | If payment was uploaded before delivery,
                | automatically mark payment received now.
                |
                */

                if (
                    $this->isDeliveredStatus(
                        $status
                    )
                ) {

                    $payment =
                        Payment::whereRaw(
                            'TRIM(UPPER(article_number)) = ?',
                            [$barcode]
                        )

                        ->latest('id')

                        ->first();


                    if ($payment) {

                        $paymentUpdate = [

                            'delivered_date' =>
                            $order->fresh()
                                ->delivery_date
                        ];


                        $payment->update(
                            $paymentUpdate
                        );


                        $payBillDate =
                            $payment->bill_date;


                        $order->update([

                            'recivedpaysts' =>
                            1,

                            'receivedcodamt' =>
                            $payment->cod_value,

                            'pay_bill_date' =>
                            $payBillDate
                        ]);


                        $paymentAutoMatched++;
                    }
                }
            }


            DB::commit();


            return back()->with(
                'delivery_success',

                "Delivery upload completed. " .

                    "Total: {$total}, " .

                    "Matched: {$matched}, " .

                    "Updated: {$updated}, " .

                    "Payment Auto Matched: {$paymentAutoMatched}, " .

                    "Not Found: {$notFound}, " .

                    "Skipped: {$skipped}"
            );
        } catch (\Throwable $e) {

            DB::rollBack();


            Log::error(
                'Delivery Upload Error',
                [
                    'message' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString()
                ]
            );


            return back()->with(
                'error',
                'Delivery upload failed: ' .
                    $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE HEADER
    |--------------------------------------------------------------------------
    */

    private function normalizeHeader($value)
    {
        $value = trim(
            (string) $value
        );

        $value = strtolower(
            $value
        );

        $value = str_replace(
            [
                "\n",
                "\r",
                "\t",
                '-',
                '/',
                '\\',
                '.',
                ' '
            ],
            '_',
            $value
        );


        $value = preg_replace(
            '/_+/',
            '_',
            $value
        );


        return trim(
            $value,
            '_'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FIRST VALUE
    |--------------------------------------------------------------------------
    */

    private function firstValue(
        array $data,
        array $keys
    ) {

        foreach ($keys as $key) {

            if (
                array_key_exists(
                    $key,
                    $data
                )
                &&
                $data[$key] !== null
                &&
                trim(
                    (string)
                    $data[$key]
                ) !== ''
            ) {

                return trim(
                    (string)
                    $data[$key]
                );
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE BARCODE
    |--------------------------------------------------------------------------
    */

    private function normalizeBarcode($value)
    {
        $value = trim(
            (string) $value
        );


        /*
        | Remove spaces from barcode.
        */

        $value = preg_replace(
            '/\s+/',
            '',
            $value
        );


        return strtoupper(
            $value
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE STATUS
    |--------------------------------------------------------------------------
    */

    private function normalizeStatus($status)
    {
        if (!$status) {

            return null;
        }


        $status = trim(
            (string) $status
        );


        $lower = strtolower(
            $status
        );


        /*
        |--------------------------------------------------------------------------
        | DELIVERED
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $lower,
                'delivered'
            )
        ) {

            return 'Delivered';
        }


        /*
        |--------------------------------------------------------------------------
        | RTO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $lower,
                'rto'
            )
            ||
            str_contains(
                $lower,
                'return to origin'
            )
            ||
            str_contains(
                $lower,
                'returned'
            )
        ) {

            return 'RTO';
        }


        /*
        |--------------------------------------------------------------------------
        | IN TRANSIT
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $lower,
                'in transit'
            )
            ||
            str_contains(
                $lower,
                'transit'
            )
            ||
            str_contains(
                $lower,
                'dispatched'
            )
            ||
            str_contains(
                $lower,
                'bagged'
            )
        ) {

            return 'In Transit';
        }


        /*
        |--------------------------------------------------------------------------
        | UNDELIVERED
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $lower,
                'undelivered'
            )
            ||
            str_contains(
                $lower,
                'attempt'
            )
        ) {

            return 'Undelivered';
        }


        return $status;
    }


    /*
    |--------------------------------------------------------------------------
    | DELIVERED CHECK
    |--------------------------------------------------------------------------
    */

    private function isDeliveredStatus($status)
    {
        if (!$status) {

            return false;
        }


        return strtolower(
            trim(
                (string) $status
            )
        ) === 'delivered';
    }


    /*
    |--------------------------------------------------------------------------
    | DATE PARSER
    |--------------------------------------------------------------------------
    */

    private function parseDate($value)
    {
        if (
            $value === null ||
            trim(
                (string) $value
            ) === ''
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | EXCEL SERIAL DATE
        |--------------------------------------------------------------------------
        */

        if (
            is_numeric($value) &&
            (float) $value > 30000
        ) {

            try {

                return Carbon::createFromTimestamp(
                    (
                        (float) $value - 25569
                    ) * 86400
                )->format('Y-m-d');
            } catch (\Throwable $e) {

                return null;
            }
        }


        $formats = [

            'd-m-Y',

            'd/m/Y',

            'd.m.Y',

            'Y-m-d',

            'Y/m/d',

            'm/d/Y',

            'd-m-Y H:i:s',

            'd/m/Y H:i:s',

            'Y-m-d H:i:s',

            'd-m-Y h:i A',

            'd/m/Y h:i A',

            'Y-m-d H:i'
        ];


        foreach ($formats as $format) {

            try {

                return Carbon::createFromFormat(
                    $format,
                    trim(
                        (string) $value
                    )
                )->format('Y-m-d');
            } catch (\Throwable $e) {

                // Continue
            }
        }


        try {

            return Carbon::parse(
                trim(
                    (string) $value
                )
            )->format('Y-m-d');
        } catch (\Throwable $e) {

            return null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | AMOUNT PARSER
    |--------------------------------------------------------------------------
    */

    private function parseAmount($value)
    {
        if (
            $value === null ||
            trim(
                (string) $value
            ) === ''
        ) {

            return 0;
        }


        $value = str_replace(
            [
                ',',
                '₹',
                'Rs.',
                'Rs',
                'INR'
            ],
            '',
            (string) $value
        );


        $value = trim(
            $value
        );


        return is_numeric($value)
            ? (float) $value
            : 0;
    }


    /*
    |--------------------------------------------------------------------------
    | PENDING PAYMENT PAGE
    |--------------------------------------------------------------------------
    */

    public function pendingPayment(Request $request)
    {
        $query = Order::query()

            ->leftJoin(
                'clients',
                'clients.id',
                '=',
                'orders.client_id'
            )

            ->whereIn(
                'orders.payment_mode',
                [
                    'COD',
                    'cod',
                    'Cash on Delivery'
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | CLIENT
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
        | PENDING
        |--------------------------------------------------------------------------
        */

        $query

            ->where(
                'orders.delivery_status',
                'Delivered'
            )

            ->where(function ($q) {

                $q->whereNull(
                    'orders.recivedpaysts'
                )

                    ->orWhere(
                        'orders.recivedpaysts',
                        0
                    );
            });


        /*
        |--------------------------------------------------------------------------
        | DATE
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
        | PRODUCT
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
        | SOURCE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('order_source')) {

            if (
                $request->order_source === 'web'
            ) {

                $query->where(function ($q) {

                    $q->whereNull(
                        'orders.order_source'
                    )

                        ->orWhere(
                            'orders.order_source',
                            ''
                        );
                });
            } else {

                $query->where(
                    'orders.order_source',
                    'whatsapp'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search =
                trim(
                    $request->search
                );


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
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $totalOrders =
            (clone $query)->count();


        $totalAmount =
            (clone $query)
            ->sum('orders.amount');


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $products =
            Order::select(
                'product'
            )

            ->whereNotNull(
                'product'
            )

            ->distinct()

            ->orderBy(
                'product'
            )

            ->pluck(
                'product'
            );


        /*
        |--------------------------------------------------------------------------
        | ORDERS
        |--------------------------------------------------------------------------
        */

        $orders =
            (clone $query)

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

            ->latest(
                'orders.delivery_date'
            )

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


    /*
    |--------------------------------------------------------------------------
    | EXPORTS
    |--------------------------------------------------------------------------
    */

    public function pendingPaymentExport(
        Request $request
    ) {

        return Excel::download(

            new PendingPaymentExport(
                $request
            ),

            'Pending_Payment_' .
                now()->format('YmdHis') .
                '.xlsx'
        );
    }


    public function export(
        Request $request
    ) {

        return Excel::download(

            new PaymentExport(
                $request
            ),

            'Payment_Report_' .
                now()->format('YmdHis') .
                '.xlsx'
        );
    }
}
