<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use App\Models\LabelSender;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Imports\RTOBarcodeImport;
use App\Models\RtoReport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;

class InventoryController extends Controller

{
    /**
     * Check if logged-in user is a client.
     */
    private function isClient()
    {
        return auth()->check()
            && auth()->user()->role === 'client';
    }

    /**
     * Get logged-in client's ID.
     */
    private function clientId()
    {
        return auth()->user()->client_id;
    }

    /**
     * Inventory - Print Labels Page
     */

    /**
     * RTO Barcode Upload / Receive
     */
    public function rto(Request $request)
    {
        return view('inventory.rto');
    }
    public function uploadRto(Request $request)
    {
        try {

            /*
        |--------------------------------------------------------------------------
        | VALIDATE EXCEL
        |--------------------------------------------------------------------------
        */

            $request->validate([
                'rtobarcodes' => 'required|mimes:xls,xlsx',
            ]);


            /*
        |--------------------------------------------------------------------------
        | READ EXCEL
        |--------------------------------------------------------------------------
        */

            $barcodes = Excel::toArray(
                new RTOBarcodeImport,
                $request->file('rtobarcodes')
            );


            /*
        |--------------------------------------------------------------------------
        | GET UNIQUE BARCODES
        |--------------------------------------------------------------------------
        */

            $barcodeList = collect($barcodes[0] ?? [])
                ->flatten()
                ->filter()
                ->map(function ($barcode) {

                    return trim((string) $barcode);
                })
                ->unique()
                ->values()
                ->toArray();


            $totalUploaded = count($barcodeList);


            if ($totalUploaded === 0) {

                return redirect()
                    ->route('inventory.rto')
                    ->with(
                        'rto_error',
                        'Excel file does not contain any valid barcode.'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | ALREADY SCANNED RTO
        |--------------------------------------------------------------------------
        */

            $existingBarcodes = RtoReport::whereIn(
                'tracking_no',
                $barcodeList
            )
                ->pluck('tracking_no')
                ->toArray();


            $skippedBarcodes = $existingBarcodes;


            /*
        |--------------------------------------------------------------------------
        | NEW BARCODES ONLY
        |--------------------------------------------------------------------------
        */

            $newBarcodes = array_values(
                array_diff(
                    $barcodeList,
                    $existingBarcodes
                )
            );


            $skippedCount = count($existingBarcodes);


            /*
        |--------------------------------------------------------------------------
        | FIND ORDERS
        |--------------------------------------------------------------------------
        */

            $orders = Order::whereIn(
                'barcode',
                $newBarcodes
            )
                ->orderBy('date', 'desc')
                ->get();


            /*
        |--------------------------------------------------------------------------
        | NOT FOUND BARCODES
        |--------------------------------------------------------------------------
        */

            $foundBarcodes = $orders
                ->pluck('barcode')
                ->filter()
                ->toArray();


            $notFoundBarcodes = array_values(
                array_diff(
                    $newBarcodes,
                    $foundBarcodes
                )
            );


            $notFoundCount = count($notFoundBarcodes);

            $foundCount = $orders->count();


            /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER RTO STATUS
        |--------------------------------------------------------------------------
        */

            if (!empty($foundBarcodes)) {

                Order::whereIn(
                    'barcode',
                    $foundBarcodes
                )
                    ->update([
                        'rtorecivedsts'  => 1,
                        'rtoreciveddate' => Carbon::now(),
                    ]);
            }


            /*
        |--------------------------------------------------------------------------
        | CREATE RTO REPORT
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Excel download nahi hoga.
        | RTO Report isi upload process me create hoga.
        |
        */

            $reportCreated = 0;

            foreach ($orders as $order) {

                if (empty($order->barcode)) {
                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | EXTRA DUPLICATE SAFETY CHECK
            |--------------------------------------------------------------------------
            */

                $alreadyExists = RtoReport::where(
                    'tracking_no',
                    $order->barcode
                )->exists();


                if ($alreadyExists) {
                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | SAVE RTO REPORT
            |--------------------------------------------------------------------------
            */

                RtoReport::create([

                    'order_id'         => $order->order_id,

                    'tracking_no'      => $order->barcode,

                    'customer_name'    => $order->customer_name,

                    'customer_phone'   => $order->customer_phone,

                    'father_name'      => $order->father_name,

                    'shipping_address' => $order->shipping_address,

                    'payment_mode'     => $order->payment_mode,

                    'amount'           => $order->amount,

                    'product'          => $order->product,

                    'quantity'         => $order->quantity,

                    'weight'           => $order->weight,

                    'order_date'       => $order->date,

                    'is_exported'      => '0',

                ]);


                $reportCreated++;
            }


            /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        */

            $message = "
            <strong>Total Uploaded:</strong> {$totalUploaded}<br>
            <strong>New RTO Found:</strong> {$foundCount}<br>
            <strong>RTO Report Created:</strong> {$reportCreated}<br>
            <strong>Already Scanned / Skipped:</strong> {$skippedCount}<br>
            <strong>Not Found:</strong> {$notFoundCount}
        ";


            /*
        |--------------------------------------------------------------------------
        | RETURN TO RTO PAGE
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->route('inventory.rto')
                ->with('rto_success', $message)
                ->with('rto_skipped', $skippedBarcodes)
                ->with('rto_not_found', $notFoundBarcodes);
        } catch (\Exception $e) {

            return redirect()
                ->route('inventory.rto')
                ->with(
                    'rto_error',
                    'RTO upload failed: ' . $e->getMessage()
                );
        }
    }
    public function printLabels(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | BASIC DATA
    |--------------------------------------------------------------------------
    */

        $user = auth()->user();

        $date = $request->input(
            'date',
            now()->toDateString()
        );

        $search = trim(
            $request->input('search', '')
        );

        $clientId = $request->input('client');

        $product = $request->input('product');

        $quantity = $request->input('quantity');

        $status = $request->input('status');

        $labelType = $request->input(
            'label_type',
            'all'
        );


        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY
    |--------------------------------------------------------------------------
    */

        $isClient = (
            isset($user->role) &&
            $user->role === 'client'
        );

        if ($isClient) {
            $clientId = $user->client_id;
        }


        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    |
    | This query contains all common filters.
    |
    | IMPORTANT:
    | Status and label_type are NOT applied here.
    |
    | Because dashboard cards need to show:
    |
    | Total
    | Printed
    | Pending
    |
    | separately.
    |
    */

        $baseQuery = Order::query();


        /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

        $baseQuery->whereDate(
            'created_at',
            $date
        );


        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY / FILTER
    |--------------------------------------------------------------------------
    */

        if ($isClient) {

            $baseQuery->where(
                'client_id',
                $user->client_id
            );
        } elseif (
            !empty($clientId) &&
            $clientId !== 'all'
        ) {

            $baseQuery->where(
                'client_id',
                $clientId
            );
        }


        /*
    |--------------------------------------------------------------------------
    | PRODUCT FILTER
    |--------------------------------------------------------------------------
    */

        if (
            !empty($product) &&
            $product !== 'all'
        ) {

            $baseQuery->where(
                'product',
                $product
            );
        }


        /*
    |--------------------------------------------------------------------------
    | QUANTITY FILTER
    |--------------------------------------------------------------------------
    */

        if (
            !empty($quantity) &&
            $quantity !== 'all'
        ) {

            $baseQuery->where(
                'quantity',
                $quantity
            );
        }


        /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

        if ($search !== '') {

            $baseQuery->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'like',
                    '%' . $search . '%'
                )

                    ->orWhere(
                        'barcode',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'customer_name',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'customer_phone',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'product',
                        'like',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'pincode',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | INDIA POST QUERY
    |--------------------------------------------------------------------------
    |
    | India Post order = barcode available
    |
    */

        $indiaPostQuery = clone $baseQuery;

        $indiaPostQuery
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '');


        /*
    |--------------------------------------------------------------------------
    | INDIA POST COUNTS
    |--------------------------------------------------------------------------
    */

        $indiaPostTotal = (clone $indiaPostQuery)
            ->count();

        $indiaPostArticles = (clone $indiaPostQuery)
            ->sum('quantity');


        $indiaPostPrinted = (clone $indiaPostQuery)
            ->where(
                'label_status',
                'printed'
            )
            ->count();

        $indiaPostPrintedArticles = (clone $indiaPostQuery)
            ->where(
                'label_status',
                'printed'
            )
            ->sum('quantity');


        $indiaPostPending = (clone $indiaPostQuery)
            ->where(function ($q) {

                $q->whereNull('label_status')
                    ->orWhere(
                        'label_status',
                        'pending'
                    );
            })
            ->count();

        $indiaPostPendingArticles = (clone $indiaPostQuery)
            ->where(function ($q) {

                $q->whereNull('label_status')
                    ->orWhere(
                        'label_status',
                        'pending'
                    );
            })
            ->sum('quantity');


        /*
    |--------------------------------------------------------------------------
    | DELIVERY / COURIER QUERY
    |--------------------------------------------------------------------------
    |
    | Delivery/Courier order = barcode NULL or empty
    |
    */

        $deliveryQuery = clone $baseQuery;

        $deliveryQuery->where(function ($q) {

            $q->whereNull('barcode')
                ->orWhere(
                    'barcode',
                    ''
                );
        });


        /*
    |--------------------------------------------------------------------------
    | DELIVERY COUNTS
    |--------------------------------------------------------------------------
    */

        $deliveryTotal = (clone $deliveryQuery)
            ->count();

        $deliveryArticles = (clone $deliveryQuery)
            ->sum('quantity');


        $deliveryPrinted = (clone $deliveryQuery)
            ->where(
                'label_status',
                'printed'
            )
            ->count();

        $deliveryPrintedArticles = (clone $deliveryQuery)
            ->where(
                'label_status',
                'printed'
            )
            ->sum('quantity');


        $deliveryPending = (clone $deliveryQuery)
            ->where(function ($q) {

                $q->whereNull('label_status')
                    ->orWhere(
                        'label_status',
                        'pending'
                    );
            })
            ->count();

        $deliveryPendingArticles = (clone $deliveryQuery)
            ->where(function ($q) {

                $q->whereNull('label_status')
                    ->orWhere(
                        'label_status',
                        'pending'
                    );
            })
            ->sum('quantity');


        /*
    |--------------------------------------------------------------------------
    | OVERALL COUNTS
    |--------------------------------------------------------------------------
    */

        $overallTotal =
            $indiaPostTotal +
            $deliveryTotal;


        $overallArticles =
            $indiaPostArticles +
            $deliveryArticles;


        $overallPrinted =
            $indiaPostPrinted +
            $deliveryPrinted;


        $overallPrintedArticles =
            $indiaPostPrintedArticles +
            $deliveryPrintedArticles;


        $overallPending =
            $indiaPostPending +
            $deliveryPending;


        $overallPendingArticles =
            $indiaPostPendingArticles +
            $deliveryPendingArticles;


        /*
    |--------------------------------------------------------------------------
    | TABLE QUERY
    |--------------------------------------------------------------------------
    */

        $query = clone $baseQuery;


        /*
    |--------------------------------------------------------------------------
    | LABEL TYPE FILTER
    |--------------------------------------------------------------------------
    */

        if ($labelType === 'india_post') {

            $query
                ->whereNotNull('barcode')
                ->where(
                    'barcode',
                    '!=',
                    ''
                );
        } elseif ($labelType === 'delivery') {

            $query->where(function ($q) {

                $q->whereNull('barcode')
                    ->orWhere(
                        'barcode',
                        ''
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | LABEL STATUS FILTER
    |--------------------------------------------------------------------------
    */

        if (
            !empty($status) &&
            $status !== 'all'
        ) {

            if ($status === 'pending') {

                $query->where(function ($q) {

                    $q->whereNull('label_status')
                        ->orWhere(
                            'label_status',
                            'pending'
                        );
                });
            } else {

                $query->where(
                    'label_status',
                    $status
                );
            }
        }


        /*
    |--------------------------------------------------------------------------
    | SORTING
    |--------------------------------------------------------------------------
    */

        $allowedSorts = [
            'order_id',
            'barcode',
            'customer_name',
            'product',
            'pincode',
            'quantity',
            'created_at',
        ];

        $sort = $request->input(
            'sort',
            'created_at'
        );

        if (
            !in_array(
                $sort,
                $allowedSorts,
                true
            )
        ) {

            $sort = 'created_at';
        }


        $direction = $request->input(
            'direction',
            'desc'
        );

        if (
            !in_array(
                $direction,
                ['asc', 'desc'],
                true
            )
        ) {

            $direction = 'desc';
        }


        /*
    |--------------------------------------------------------------------------
    | ORDERS
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->orderBy(
                $sort,
                $direction
            )
            ->paginate(50)
            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | INDIA POST ORDERS
    |--------------------------------------------------------------------------
    */

        $indiaPostOrders = $indiaPostTotal;

        /*
    |--------------------------------------------------------------------------
    | DELIVERY ORDERS
    |--------------------------------------------------------------------------
    */

        $deliveryOrders = $deliveryTotal;


        /*
    |--------------------------------------------------------------------------
    | CLIENT LIST
    |--------------------------------------------------------------------------
    */

        if ($isClient) {

            $clients = Client::where(
                'id',
                $user->client_id
            )->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }


        /*
    |--------------------------------------------------------------------------
    | SENDER LIST
    |--------------------------------------------------------------------------
    */

        if ($isClient) {

            $senders = LabelSender::where(
                'client_id',
                $user->client_id
            )
                ->orderBy(
                    'customer_name'
                )
                ->get();
        } else {

            $senders = LabelSender::orderBy(
                'customer_name'
            )->get();
        }


        /*
    |--------------------------------------------------------------------------
    | PRODUCT LIST
    |--------------------------------------------------------------------------
    */

        $productQuery = Order::query();

        $productQuery->whereDate(
            'created_at',
            $date
        );

        if ($isClient) {

            $productQuery->where(
                'client_id',
                $user->client_id
            );
        } elseif (
            !empty($clientId) &&
            $clientId !== 'all'
        ) {

            $productQuery->where(
                'client_id',
                $clientId
            );
        }


        $products = $productQuery
            ->whereNotNull('product')
            ->where(
                'product',
                '!=',
                ''
            )
            ->select('product')
            ->distinct()
            ->orderBy('product')
            ->pluck('product');


        /*
    |--------------------------------------------------------------------------
    | QUANTITY LIST
    |--------------------------------------------------------------------------
    */

        $quantityQuery = Order::query();

        $quantityQuery->whereDate(
            'created_at',
            $date
        );

        if ($isClient) {

            $quantityQuery->where(
                'client_id',
                $user->client_id
            );
        } elseif (
            !empty($clientId) &&
            $clientId !== 'all'
        ) {

            $quantityQuery->where(
                'client_id',
                $clientId
            );
        }


        $quantities = $quantityQuery
            ->whereNotNull('quantity')
            ->where(
                'quantity',
                '>',
                0
            )
            ->select('quantity')
            ->distinct()
            ->orderBy('quantity')
            ->pluck('quantity');


        /*
    |--------------------------------------------------------------------------
    | QUANTITY SUMMARY
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Qty 1 = 10 Orders / 10 Articles
    | Qty 2 = 5 Orders / 10 Articles
    |
    */

        $quantitySummaryQuery = Order::query();

        $quantitySummaryQuery->whereDate(
            'created_at',
            $date
        );

        if ($isClient) {

            $quantitySummaryQuery->where(
                'client_id',
                $user->client_id
            );
        } elseif (
            !empty($clientId) &&
            $clientId !== 'all'
        ) {

            $quantitySummaryQuery->where(
                'client_id',
                $clientId
            );
        }


        $quantitySummary = $quantitySummaryQuery
            ->whereNotNull('quantity')
            ->where(
                'quantity',
                '>',
                0
            )
            ->selectRaw(
                'quantity,
             COUNT(*) as orders_count,
             SUM(quantity) as total_articles'
            )
            ->groupBy('quantity')
            ->orderBy('quantity')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | CURRENT FILTER TOTALS
    |--------------------------------------------------------------------------
    */

        $filteredOrders = (clone $query)
            ->count();

        $filteredArticles = (clone $query)
            ->sum('quantity');


        /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'inventory.print-labels',
            compact(

                'orders',

                'clients',

                'senders',

                'date',

                'products',

                'quantities',

                'quantitySummary',

                'labelType',

                'status',

                'search',

                'clientId',

                'product',

                'quantity',

                'sort',

                'direction',

                'isClient',

                /*
            | India Post
            */

                'indiaPostOrders',

                'indiaPostTotal',

                'indiaPostArticles',

                'indiaPostPrinted',

                'indiaPostPrintedArticles',

                'indiaPostPending',

                'indiaPostPendingArticles',

                /*
            | Delivery
            */

                'deliveryOrders',

                'deliveryTotal',

                'deliveryArticles',

                'deliveryPrinted',

                'deliveryPrintedArticles',

                'deliveryPending',

                'deliveryPendingArticles',

                /*
            | Overall
            */

                'overallTotal',

                'overallArticles',

                'overallPrinted',

                'overallPrintedArticles',

                'overallPending',

                'overallPendingArticles',

                /*
            | Current table filter
            */

                'filteredOrders',

                'filteredArticles'
            )
        );
    }


    /**
     * Generate labels PDF from selected orders.
     */
    public function generateLabels(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'sender_id' => 'required|exists:label_senders,id',
        ]);

        $sender = LabelSender::findOrFail(
            $request->sender_id
        );

        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY - SENDER
    |--------------------------------------------------------------------------
    */

        if (
            $this->isClient()
            &&
            $sender->client_id != $this->clientId()
        ) {
            abort(403);
        }


        /*
    |--------------------------------------------------------------------------
    | GET SELECTED ORDERS
    |--------------------------------------------------------------------------
    */

        $query = Order::whereIn(
            'id',
            $request->order_ids
        );

        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY - ORDERS
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }


        $orders = $query
            ->orderBy('id')
            ->get();


        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No orders selected for label printing.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | GENERATE PDF
    |--------------------------------------------------------------------------
    */

        try {

            $pdf = Pdf::loadView(
                'inventory.labels-pdf',
                [
                    'orders' => $orders,
                    'sender' => $sender,
                ]
            )
                ->setPaper(
                    [0, 0, 288, 432],
                    'portrait'
                )
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'sans-serif',
                ]);


            /*
        |--------------------------------------------------------------------------
        | PDF CONTENT
        |--------------------------------------------------------------------------
        */

            $pdfContent = $pdf->output();


            /*
        |--------------------------------------------------------------------------
        | ONLY AFTER PDF SUCCESSFULLY GENERATED
        | MARK ORDERS AS PRINTED
        |--------------------------------------------------------------------------
        */

            Order::whereIn(
                'id',
                $orders->pluck('id')
            )->update([
                'label_status' => 'printed',

                'label_printed_at' => now(),

                'label_print_count' => DB::raw(
                    'COALESCE(label_print_count, 0) + 1'
                ),
            ]);


            /*
        |--------------------------------------------------------------------------
        | DOWNLOAD
        |--------------------------------------------------------------------------
        */

            $fileName =
                'shipping_labels_' .
                now()->format('Y-m-d_H-i-s') .
                '.pdf';


            return response(
                $pdfContent,
                200,
                [
                    'Content-Type' =>
                    'application/pdf',

                    'Content-Disposition' =>
                    'attachment; filename="' .
                        $fileName .
                        '"',
                ]
            );
        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Label PDF generation failed: ' .
                    $e->getMessage()
            );
        }
    }
}
