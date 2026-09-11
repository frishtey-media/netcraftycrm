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
use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ClientProduct;
use Illuminate\Support\Facades\Log;

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


    public function rto(Request $request)
    {
        return view('inventory.rto');
    }
    public function uploadRto(Request $request)
    {
        try {

            /*
        |--------------------------------------------------------------------------
        | VALIDATION
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
        | BARCODE LIST
        |--------------------------------------------------------------------------
        */

            $barcodeList = collect($barcodes[0] ?? [])
                ->flatten()
                ->filter(function ($barcode) {

                    return trim((string) $barcode) !== '';
                })
                ->map(function ($barcode) {

                    return trim((string) $barcode);
                })
                ->unique()
                ->values()
                ->toArray();


            $totalUploaded =
                count($barcodeList);


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
        | ALREADY PROCESSED
        |--------------------------------------------------------------------------
        */

            $existingBarcodes =
                RtoReport::whereIn(
                    'tracking_no',
                    $barcodeList
                )
                ->pluck('tracking_no')
                ->map(function ($barcode) {

                    return trim((string) $barcode);
                })
                ->unique()
                ->values()
                ->toArray();


            /*
        |--------------------------------------------------------------------------
        | NEW BARCODES
        |--------------------------------------------------------------------------
        */

            $newBarcodes =
                array_values(
                    array_diff(
                        $barcodeList,
                        $existingBarcodes
                    )
                );


            $skippedBarcodes =
                $existingBarcodes;

            $skippedCount =
                count($existingBarcodes);


            /*
        |--------------------------------------------------------------------------
        | ALL ALREADY PROCESSED
        |--------------------------------------------------------------------------
        */

            if (empty($newBarcodes)) {

                return redirect()
                    ->route('inventory.rto')
                    ->with(
                        'rto_success',
                        "
                    <strong>Total Uploaded:</strong>
                    {$totalUploaded}<br>

                    <strong>New RTO Found:</strong>
                    0<br>

                    <strong>RTO Report Created:</strong>
                    0<br>

                    <strong>Products Restocked:</strong>
                    0<br>

                    <strong>Total Quantity Restored:</strong>
                    0<br>

                    <strong>Inventory Skipped (warehousetype = 1):</strong>
                    0<br>

                    <strong>Already Scanned / Skipped:</strong>
                    {$skippedCount}<br>

                    <strong>Not Found:</strong>
                    0
                    "
                    )
                    ->with(
                        'rto_skipped',
                        $skippedBarcodes
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

            $result = DB::transaction(
                function () use ($newBarcodes) {


                    /*
                |--------------------------------------------------------------------------
                | FIND ORDERS
                |--------------------------------------------------------------------------
                */

                    $orders =
                        Order::whereIn(
                            'barcode',
                            $newBarcodes
                        )
                        ->orderBy(
                            'date',
                            'desc'
                        )
                        ->lockForUpdate()
                        ->get();


                    /*
                |--------------------------------------------------------------------------
                | FOUND BARCODES
                |--------------------------------------------------------------------------
                */

                    $foundBarcodes =
                        $orders
                        ->pluck('barcode')
                        ->filter()
                        ->map(function ($barcode) {

                            return trim(
                                (string) $barcode
                            );
                        })
                        ->unique()
                        ->values()
                        ->toArray();


                    /*
                |--------------------------------------------------------------------------
                | NOT FOUND
                |--------------------------------------------------------------------------
                */

                    $notFoundBarcodes =
                        array_values(
                            array_diff(
                                $newBarcodes,
                                $foundBarcodes
                            )
                        );


                    /*
                |--------------------------------------------------------------------------
                | COUNTERS
                |--------------------------------------------------------------------------
                */

                    $reportCreated = 0;

                    $stockRestored = 0;

                    $productsRestored = 0;

                    $inventorySkipped = 0;

                    $restoreDetails = [];

                    $mappingErrors = [];


                    /*
                |--------------------------------------------------------------------------
                | PROCESS EACH ORDER
                |--------------------------------------------------------------------------
                */

                    foreach ($orders as $order) {


                        $barcode =
                            trim(
                                (string)
                                $order->barcode
                            );


                        if ($barcode === '') {

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | QUANTITY
                    |--------------------------------------------------------------------------
                    */

                        $quantity =
                            (int)
                            $order->quantity;


                        if ($quantity <= 0) {

                            $mappingErrors[] = [

                                'barcode' =>
                                $barcode,

                                'order_id' =>
                                $order->order_id,

                                'product' =>
                                $order->product,

                                'message' =>
                                'Invalid RTO quantity: ' .
                                    $order->quantity,

                            ];

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | CLIENT
                    |--------------------------------------------------------------------------
                    */

                        $client =
                            DB::table('clients')
                            ->where(
                                'id',
                                $order->client_id
                            )
                            ->first();


                        if (!$client) {

                            $mappingErrors[] = [

                                'barcode' =>
                                $barcode,

                                'order_id' =>
                                $order->order_id,

                                'product' =>
                                $order->product,

                                'message' =>
                                'Client not found.',

                            ];

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | WAREHOUSE TYPE = 1
                    |--------------------------------------------------------------------------
                    |
                    | Inventory manage nahi hoti.
                    |
                    | RTO Report create hoga.
                    | Order RTO Received hoga.
                    | Inventory skip hogi.
                    | Mapping error nahi aayega.
                    |
                    */

                        if (
                            (int)
                            $client->warehousetype === 1
                        ) {


                            /*
                        |--------------------------------------------------------------------------
                        | RTO REPORT
                        |--------------------------------------------------------------------------
                        */

                            RtoReport::create([

                                'order_id' =>
                                $order->order_id,

                                'tracking_no' =>
                                $barcode,

                                'customer_name' =>
                                $order->customer_name,

                                'customer_phone' =>
                                $order->customer_phone,

                                'father_name' =>
                                $order->father_name,

                                'shipping_address' =>
                                $order->shipping_address,

                                'payment_mode' =>
                                $order->payment_mode,

                                'amount' =>
                                $order->amount,

                                'product' =>
                                $order->product,

                                'quantity' =>
                                $quantity,

                                'weight' =>
                                $order->weight,

                                'order_date' =>
                                $order->date,

                                'is_exported' =>
                                0,

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | MARK RTO RECEIVED
                        |--------------------------------------------------------------------------
                        */

                            Order::where(
                                'id',
                                $order->id
                            )->update([

                                'rtorecivedsts' =>
                                1,

                                'rtoreciveddate' =>
                                now(),

                            ]);


                            $reportCreated++;

                            $inventorySkipped++;

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | WAREHOUSE TYPE = 0
                    |--------------------------------------------------------------------------
                    |
                    | Inventory manage karni hai.
                    |
                    */


                        $productName =
                            strtolower(
                                trim(
                                    (string)
                                    ($order->product ?? '')
                                )
                            );


                        /*
                    |--------------------------------------------------------------------------
                    | HARD CODED CLIENT
                    |--------------------------------------------------------------------------
                    |
                    | Client ID = 5
                    |
                    */

                        $isClient5 =
                            (int)
                            $order->client_id === 5;


                        /*
                    |--------------------------------------------------------------------------
                    | HARD CODED COMBO DETECTION
                    |--------------------------------------------------------------------------
                    |
                    | Hair Oil + Shampoo
                    |
                    */

                        $isHairOilShampooCombo =
                            $isClient5
                            &&
                            str_contains(
                                $productName,
                                'hair oil'
                            )
                            &&
                            str_contains(
                                $productName,
                                'shampoo'
                            );


                        /*
                    |--------------------------------------------------------------------------
                    | COMBO PROCESS
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $isHairOilShampooCombo
                        ) {


                            /*
                        |--------------------------------------------------------------------------
                        | FIND HAIR OIL CLIENT PRODUCT
                        |--------------------------------------------------------------------------
                        */

                            $hairOilClientProduct =
                                ClientProduct::where(
                                    'client_id',
                                    $order->client_id
                                )
                                ->whereRaw(
                                    'LOWER(shopify_product_name) LIKE ?',
                                    ['%hair oil%']
                                )
                                ->whereRaw(
                                    'LOWER(shopify_product_name) NOT LIKE ?',
                                    ['%shampoo%']
                                )
                                ->first();


                            /*
                        |--------------------------------------------------------------------------
                        | FIND SHAMPOO CLIENT PRODUCT
                        |--------------------------------------------------------------------------
                        |
                        | Combo ko exclude karenge.
                        |
                        */

                            $shampooClientProduct =
                                ClientProduct::where(
                                    'client_id',
                                    $order->client_id
                                )
                                ->whereRaw(
                                    'LOWER(shopify_product_name) LIKE ?',
                                    ['%shampoo%']
                                )
                                ->whereRaw(
                                    'LOWER(shopify_product_name) NOT LIKE ?',
                                    ['%hair oil%']
                                )
                                ->first();


                            /*
                        |--------------------------------------------------------------------------
                        | HAIR OIL INVENTORY
                        |--------------------------------------------------------------------------
                        */

                            $hairOilProduct = null;


                            if (
                                $hairOilClientProduct
                            ) {

                                $hairOilQuery =
                                    Product::where(
                                        'client_id',
                                        $order->client_id
                                    )
                                    ->where(
                                        'name',
                                        $hairOilClientProduct->id
                                    );


                                /*
                            |--------------------------------------------------------------------------
                            | FIRST TRY ORDER WAREHOUSE
                            |--------------------------------------------------------------------------
                            */

                                if (
                                    !empty($order->warehouse_id)
                                ) {

                                    $hairOilProduct =
                                        (clone $hairOilQuery)
                                        ->where(
                                            'warehouse_id',
                                            $order->warehouse_id
                                        )
                                        ->lockForUpdate()
                                        ->first();
                                }


                                /*
                            |--------------------------------------------------------------------------
                            | FALLBACK ANY WAREHOUSE
                            |--------------------------------------------------------------------------
                            */

                                if (!$hairOilProduct) {

                                    $hairOilProduct =
                                        $hairOilQuery
                                        ->lockForUpdate()
                                        ->first();
                                }
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | SHAMPOO INVENTORY
                        |--------------------------------------------------------------------------
                        */

                            $shampooProduct = null;


                            if (
                                $shampooClientProduct
                            ) {

                                $shampooQuery =
                                    Product::where(
                                        'client_id',
                                        $order->client_id
                                    )
                                    ->where(
                                        'name',
                                        $shampooClientProduct->id
                                    );


                                /*
                            |--------------------------------------------------------------------------
                            | FIRST TRY ORDER WAREHOUSE
                            |--------------------------------------------------------------------------
                            */

                                if (
                                    !empty($order->warehouse_id)
                                ) {

                                    $shampooProduct =
                                        (clone $shampooQuery)
                                        ->where(
                                            'warehouse_id',
                                            $order->warehouse_id
                                        )
                                        ->lockForUpdate()
                                        ->first();
                                }


                                /*
                            |--------------------------------------------------------------------------
                            | FALLBACK ANY WAREHOUSE
                            |--------------------------------------------------------------------------
                            */

                                if (!$shampooProduct) {

                                    $shampooProduct =
                                        $shampooQuery
                                        ->lockForUpdate()
                                        ->first();
                                }
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | EXTRA FALLBACK:
                        | SEARCH CLIENT PRODUCT "SHAMPOO STOCK"
                        |--------------------------------------------------------------------------
                        |
                        | Agar normal shampoo mapping nahi mili,
                        | to exact/partial Shampoo Stock client product
                        | find karenge.
                        |
                        */

                            if (!$shampooProduct) {


                                $shampooStockClientProduct =
                                    ClientProduct::where(
                                        'client_id',
                                        $order->client_id
                                    )
                                    ->whereRaw(
                                        'LOWER(TRIM(shopify_product_name)) = ?',
                                        ['shampoo stock']
                                    )
                                    ->first();


                                if (
                                    $shampooStockClientProduct
                                ) {

                                    $shampooStockQuery =
                                        Product::where(
                                            'client_id',
                                            $order->client_id
                                        )
                                        ->where(
                                            'name',
                                            $shampooStockClientProduct->id
                                        );


                                    if (
                                        !empty($order->warehouse_id)
                                    ) {

                                        $shampooProduct =
                                            (clone $shampooStockQuery)
                                            ->where(
                                                'warehouse_id',
                                                $order->warehouse_id
                                            )
                                            ->lockForUpdate()
                                            ->first();
                                    }


                                    if (!$shampooProduct) {

                                        $shampooProduct =
                                            $shampooStockQuery
                                            ->lockForUpdate()
                                            ->first();
                                    }
                                }
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | FINAL CHECK
                        |--------------------------------------------------------------------------
                        */

                            if (
                                !$hairOilProduct
                                ||
                                !$shampooProduct
                            ) {

                                $missing = [];


                                if (!$hairOilProduct) {

                                    $missing[] =
                                        'Hair Oil';
                                }


                                if (!$shampooProduct) {

                                    $missing[] =
                                        'Shampoo Stock';
                                }


                                $mappingErrors[] = [

                                    'barcode' =>
                                    $barcode,

                                    'order_id' =>
                                    $order->order_id,

                                    'product' =>
                                    $order->product,

                                    'message' =>
                                    'Inventory product not found: ' .
                                        implode(
                                            ', ',
                                            $missing
                                        ),

                                ];

                                continue;
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | COMBO QUANTITY
                        |--------------------------------------------------------------------------
                        |
                        | 1+1 Pack
                        |
                        */

                            $hairOilQty =
                                $quantity;

                            $shampooQty =
                                $quantity;


                            /*
                        |--------------------------------------------------------------------------
                        | HAIR OIL STOCK
                        |--------------------------------------------------------------------------
                        */

                            $hairOilOldStock =
                                (int)
                                $hairOilProduct
                                    ->low_stock_alert;


                            $hairOilNewStock =
                                $hairOilOldStock
                                +
                                $hairOilQty;


                            $hairOilProduct->update([

                                'low_stock_alert' =>
                                $hairOilNewStock,

                                'total_price' =>
                                $hairOilNewStock
                                    *
                                    (float)
                                    $hairOilProduct->price,

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | HAIR OIL STOCK MOVEMENT
                        |--------------------------------------------------------------------------
                        */

                            StockMovement::create([

                                'product_id' =>
                                $hairOilProduct->id,

                                'warehouse_id' =>
                                $hairOilProduct->warehouse_id,

                                'quantity' =>
                                $hairOilQty,

                                'type' =>
                                'rto_restored',

                                'price' =>
                                $hairOilProduct->price,

                                'movement_date' =>
                                now(),

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | SHAMPOO STOCK
                        |--------------------------------------------------------------------------
                        */

                            $shampooOldStock =
                                (int)
                                $shampooProduct
                                    ->low_stock_alert;


                            $shampooNewStock =
                                $shampooOldStock
                                +
                                $shampooQty;


                            $shampooProduct->update([

                                'low_stock_alert' =>
                                $shampooNewStock,

                                'total_price' =>
                                $shampooNewStock
                                    *
                                    (float)
                                    $shampooProduct->price,

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | SHAMPOO STOCK MOVEMENT
                        |--------------------------------------------------------------------------
                        */

                            StockMovement::create([

                                'product_id' =>
                                $shampooProduct->id,

                                'warehouse_id' =>
                                $shampooProduct->warehouse_id,

                                'quantity' =>
                                $shampooQty,

                                'type' =>
                                'rto_restored',

                                'price' =>
                                $shampooProduct->price,

                                'movement_date' =>
                                now(),

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | RTO REPORT
                        |--------------------------------------------------------------------------
                        */

                            RtoReport::create([

                                'order_id' =>
                                $order->order_id,

                                'tracking_no' =>
                                $barcode,

                                'customer_name' =>
                                $order->customer_name,

                                'customer_phone' =>
                                $order->customer_phone,

                                'father_name' =>
                                $order->father_name,

                                'shipping_address' =>
                                $order->shipping_address,

                                'payment_mode' =>
                                $order->payment_mode,

                                'amount' =>
                                $order->amount,

                                'product' =>
                                $order->product,

                                'quantity' =>
                                $quantity,

                                'weight' =>
                                $order->weight,

                                'order_date' =>
                                $order->date,

                                'is_exported' =>
                                0,

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | MARK RTO RECEIVED
                        |--------------------------------------------------------------------------
                        */

                            Order::where(
                                'id',
                                $order->id
                            )->update([

                                'rtorecivedsts' =>
                                1,

                                'rtoreciveddate' =>
                                now(),

                            ]);


                            /*
                        |--------------------------------------------------------------------------
                        | COUNTERS
                        |--------------------------------------------------------------------------
                        */

                            $reportCreated++;

                            /*
                        | 2 inventory products:
                        | Hair Oil + Shampoo
                        */

                            $productsRestored += 2;


                            /*
                        | Total physical quantity restored
                        */

                            $stockRestored +=
                                $hairOilQty
                                +
                                $shampooQty;


                            /*
                        |--------------------------------------------------------------------------
                        | DETAILS
                        |--------------------------------------------------------------------------
                        */

                            $restoreDetails[] = [

                                'barcode' =>
                                $barcode,

                                'order_id' =>
                                $order->order_id,

                                'product' =>
                                $order->product,

                                'quantity' =>
                                $quantity,

                                'components' => [

                                    [

                                        'product' =>
                                        'Hair Oil',

                                        'quantity' =>
                                        $hairOilQty,

                                        'old_stock' =>
                                        $hairOilOldStock,

                                        'new_stock' =>
                                        $hairOilNewStock,

                                    ],

                                    [

                                        'product' =>
                                        'Shampoo Stock',

                                        'quantity' =>
                                        $shampooQty,

                                        'old_stock' =>
                                        $shampooOldStock,

                                        'new_stock' =>
                                        $shampooNewStock,

                                    ],

                                ],

                            ];


                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | NORMAL SINGLE PRODUCT
                    |--------------------------------------------------------------------------
                    */

                        $clientProduct =
                            $this->findClientProduct(
                                $order->product
                            );


                        if (!$clientProduct) {

                            $mappingErrors[] = [

                                'barcode' =>
                                $barcode,

                                'order_id' =>
                                $order->order_id,

                                'product' =>
                                $order->product,

                                'message' =>
                                'Client product mapping not found.',

                            ];

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | FIND INVENTORY PRODUCT
                    |--------------------------------------------------------------------------
                    */

                        $productQuery =
                            Product::where(
                                'client_id',
                                $order->client_id
                            )
                            ->where(
                                'name',
                                $clientProduct->id
                            );


                        /*
                    |--------------------------------------------------------------------------
                    | TRY ORDER WAREHOUSE FIRST
                    |--------------------------------------------------------------------------
                    */

                        $product = null;


                        if (
                            !empty($order->warehouse_id)
                        ) {

                            $product =
                                (clone $productQuery)
                                ->where(
                                    'warehouse_id',
                                    $order->warehouse_id
                                )
                                ->lockForUpdate()
                                ->first();
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | FALLBACK ANY WAREHOUSE
                    |--------------------------------------------------------------------------
                    */

                        if (!$product) {

                            $product =
                                $productQuery
                                ->lockForUpdate()
                                ->first();
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | INVENTORY PRODUCT NOT FOUND
                    |--------------------------------------------------------------------------
                    */

                        if (!$product) {

                            $mappingErrors[] = [

                                'barcode' =>
                                $barcode,

                                'order_id' =>
                                $order->order_id,

                                'product' =>
                                $order->product,

                                'client_product_id' =>
                                $clientProduct->id,

                                'client_id' =>
                                $order->client_id,

                                'message' =>
                                'Inventory product not found for this client/product.',

                            ];

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | OLD STOCK
                    |--------------------------------------------------------------------------
                    */

                        $oldStock =
                            (int)
                            $product->low_stock_alert;


                        /*
                    |--------------------------------------------------------------------------
                    | NEW STOCK
                    |--------------------------------------------------------------------------
                    */

                        $newStock =
                            $oldStock
                            +
                            $quantity;


                        /*
                    |--------------------------------------------------------------------------
                    | UPDATE STOCK
                    |--------------------------------------------------------------------------
                    */

                        $product->update([

                            'low_stock_alert' =>
                            $newStock,

                            'total_price' =>
                            $newStock
                                *
                                (float)
                                $product->price,

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | STOCK MOVEMENT
                    |--------------------------------------------------------------------------
                    */

                        StockMovement::create([

                            'product_id' =>
                            $product->id,

                            'warehouse_id' =>
                            $product->warehouse_id,

                            'quantity' =>
                            $quantity,

                            'type' =>
                            'rto_restored',

                            'price' =>
                            $product->price,

                            'movement_date' =>
                            now(),

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | RTO REPORT
                    |--------------------------------------------------------------------------
                    */

                        RtoReport::create([

                            'order_id' =>
                            $order->order_id,

                            'tracking_no' =>
                            $barcode,

                            'customer_name' =>
                            $order->customer_name,

                            'customer_phone' =>
                            $order->customer_phone,

                            'father_name' =>
                            $order->father_name,

                            'shipping_address' =>
                            $order->shipping_address,

                            'payment_mode' =>
                            $order->payment_mode,

                            'amount' =>
                            $order->amount,

                            'product' =>
                            $order->product,

                            'quantity' =>
                            $quantity,

                            'weight' =>
                            $order->weight,

                            'order_date' =>
                            $order->date,

                            'is_exported' =>
                            0,

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | MARK RTO RECEIVED
                    |--------------------------------------------------------------------------
                    */

                        Order::where(
                            'id',
                            $order->id
                        )->update([

                            'rtorecivedsts' =>
                            1,

                            'rtoreciveddate' =>
                            now(),

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | COUNTERS
                    |--------------------------------------------------------------------------
                    */

                        $reportCreated++;

                        $stockRestored +=
                            $quantity;

                        $productsRestored++;


                        /*
                    |--------------------------------------------------------------------------
                    | DETAILS
                    |--------------------------------------------------------------------------
                    */

                        $restoreDetails[] = [

                            'barcode' =>
                            $barcode,

                            'order_id' =>
                            $order->order_id,

                            'product' =>
                            $clientProduct
                                ->shopify_product_name,

                            'quantity' =>
                            $quantity,

                            'old_stock' =>
                            $oldStock,

                            'new_stock' =>
                            $newStock,

                            'warehouse_id' =>
                            $product->warehouse_id,

                        ];
                    }


                    /*
                |--------------------------------------------------------------------------
                | RETURN TRANSACTION RESULT
                |--------------------------------------------------------------------------
                */

                    return [

                        'orders' =>
                        $orders,

                        'foundBarcodes' =>
                        $foundBarcodes,

                        'notFoundBarcodes' =>
                        $notFoundBarcodes,

                        'reportCreated' =>
                        $reportCreated,

                        'stockRestored' =>
                        $stockRestored,

                        'productsRestored' =>
                        $productsRestored,

                        'inventorySkipped' =>
                        $inventorySkipped,

                        'restoreDetails' =>
                        $restoreDetails,

                        'mappingErrors' =>
                        $mappingErrors,

                    ];
                }
            );


            /*
        |--------------------------------------------------------------------------
        | RESULT COUNTERS
        |--------------------------------------------------------------------------
        */

            $foundCount =
                count(
                    $result['foundBarcodes']
                );


            $notFoundBarcodes =
                $result['notFoundBarcodes'];


            $notFoundCount =
                count(
                    $notFoundBarcodes
                );


            $reportCreated =
                $result['reportCreated'];


            $stockRestored =
                $result['stockRestored'];


            $productsRestored =
                $result['productsRestored'];


            $inventorySkipped =
                $result['inventorySkipped'] ?? 0;


            /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        */

            $message = "

        <strong>Total Uploaded:</strong>
        {$totalUploaded}<br>

        <strong>New RTO Found:</strong>
        {$foundCount}<br>

        <strong>RTO Report Created:</strong>
        {$reportCreated}<br>

        <strong>Products Restocked:</strong>
        {$productsRestored}<br>

        <strong>Total Quantity Restored:</strong>
        {$stockRestored}<br>

        <strong>Inventory Skipped (warehousetype = 1):</strong>
        {$inventorySkipped}<br>

        <strong>Already Scanned / Skipped:</strong>
        {$skippedCount}<br>

        <strong>Not Found:</strong>
        {$notFoundCount}

        ";


            /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->route('inventory.rto')
                ->with(
                    'rto_success',
                    $message
                )
                ->with(
                    'rto_skipped',
                    $skippedBarcodes
                )
                ->with(
                    'rto_not_found',
                    $notFoundBarcodes
                )
                ->with(
                    'rto_restore_details',
                    $result['restoreDetails']
                )
                ->with(
                    'rto_mapping_errors',
                    $result['mappingErrors']
                );
        } catch (\Throwable $e) {

            /*
        |--------------------------------------------------------------------------
        | LOG ERROR
        |--------------------------------------------------------------------------
        */

            Log::error(
                'RTO Stock Restoration Failed',
                [

                    'message' =>
                    $e->getMessage(),

                    'file' =>
                    $e->getFile(),

                    'line' =>
                    $e->getLine(),

                    'trace' =>
                    $e->getTraceAsString(),

                ]
            );


            /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->route('inventory.rto')
                ->with(
                    'rto_error',
                    'RTO upload failed: ' .
                        $e->getMessage()
                );
        }
    }
    public function printLabels(Request $request)
    {

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

        $perPage = (int) $request->input('per_page', 1000);

        $allowedPerPage = [50, 100, 250, 500, 1000];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 1000;
        }
        $isClient = (
            isset($user->role) &&
            $user->role === 'client'
        );

        if ($isClient) {
            $clientId = $user->client_id;
        }

        $baseQuery = Order::query();

        $baseQuery->whereDate(
            'created_at',
            $date
        );

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

        if (
            !empty($product) &&
            $product !== 'all'
        ) {

            $baseQuery->where(
                'product',
                $product
            );
        }

        if (
            !empty($quantity) &&
            $quantity !== 'all'
        ) {

            $baseQuery->where(
                'quantity',
                $quantity
            );
        }

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

        $indiaPostQuery = clone $baseQuery;

        $indiaPostQuery
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '');

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

        $deliveryQuery = clone $baseQuery;

        $deliveryQuery->where(function ($q) {

            $q->whereNull('barcode')
                ->orWhere(
                    'barcode',
                    ''
                );
        });

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

        $query = clone $baseQuery;

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

        $orders = $query
            ->orderBy(
                $sort,
                $direction
            )
            ->paginate($perPage)
            ->withQueryString();

        $indiaPostOrders = $indiaPostTotal;

        $deliveryOrders = $deliveryTotal;


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

        $filteredOrders = (clone $query)
            ->count();

        $filteredArticles = (clone $query)
            ->sum('quantity');

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

                'indiaPostOrders',

                'indiaPostTotal',

                'indiaPostArticles',

                'indiaPostPrinted',

                'indiaPostPrintedArticles',

                'indiaPostPending',

                'indiaPostPendingArticles',

                'deliveryOrders',

                'deliveryTotal',

                'deliveryArticles',

                'deliveryPrinted',

                'deliveryPrintedArticles',

                'deliveryPending',

                'deliveryPendingArticles',

                'overallTotal',

                'overallArticles',

                'overallPrinted',

                'overallPrintedArticles',

                'overallPending',

                'overallPendingArticles',

                'filteredOrders',
                'perPage',

                'filteredArticles'
            )
        );
    }
    private function findClientProduct($productName)
    {
        $originalName = trim((string) $productName);

        if ($originalName === '') {
            return null;
        }

        /*
    |--------------------------------------------------------------------------
    | NORMALIZE INPUT
    |--------------------------------------------------------------------------
    */

        $cleanName = $this->normalizeProductName(
            $originalName
        );


        /*
    |--------------------------------------------------------------------------
    | GET ALL CLIENT PRODUCTS
    |--------------------------------------------------------------------------
    */

        $clientProducts = ClientProduct::select(
            'id',
            'shopify_product_name'
        )->get();


        /*
    |--------------------------------------------------------------------------
    | MATCH
    |--------------------------------------------------------------------------
    */

        foreach ($clientProducts as $clientProduct) {

            $dbName = $this->normalizeProductName(
                $clientProduct->shopify_product_name
            );


            /*
        | Exact normalized match
        */

            if ($cleanName === $dbName) {
                return $clientProduct;
            }


            /*
        | Base-name match
        */

            if (
                $this->isProductBaseMatch(
                    $cleanName,
                    $dbName
                )
            ) {
                return $clientProduct;
            }
        }


        return null;
    }
    private function isProductBaseMatch(
        $orderName,
        $databaseName
    ) {
        /*
    |--------------------------------------------------------------------------
    | EXACT
    |--------------------------------------------------------------------------
    */

        if ($orderName === $databaseName) {
            return true;
        }


        /*
    |--------------------------------------------------------------------------
    | ORDER NAME STARTS WITH DATABASE NAME
    |--------------------------------------------------------------------------
    */

        if (
            str_starts_with(
                $orderName,
                $databaseName
            )
        ) {
            return true;
        }


        /*
    |--------------------------------------------------------------------------
    | DATABASE NAME STARTS WITH ORDER NAME
    |--------------------------------------------------------------------------
    */

        if (
            str_starts_with(
                $databaseName,
                $orderName
            )
        ) {
            return true;
        }


        return false;
    }
    private function normalizeProductName($name)
    {
        $name = trim((string) $name);


        /*
    |--------------------------------------------------------------------------
    | REMOVE QTY
    |--------------------------------------------------------------------------
    */

        $name = preg_replace(
            '/\s*\(Qty\s*:\s*\d+\)\s*$/i',
            '',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | FIX COMMON UTF-8 / MOJIBAKE DASHES
    |--------------------------------------------------------------------------
    */

        $name = str_replace(
            [
                'â€“',
                'â€”',
                'â€" ',
                'â€"',
                '–',
                '—',
                '−',
            ],
            ' ',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | NORMALIZE + SIGN
    |--------------------------------------------------------------------------
    */

        $name = preg_replace(
            '/\s*\+\s*/',
            ' ',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | REMOVE PACK OF
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Product - 200 ML (Pack Of 1)
    |
    */

        $name = preg_replace(
            '/\s*\(Pack\s*Of\s*\d+\)\s*/i',
            ' ',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | REMOVE COMMON SIZE INFORMATION
    |--------------------------------------------------------------------------
    |
    | Only for matching purpose.
    |
    */

        $name = preg_replace(
            '/\s*-\s*\d+\s*(ML|GM|G|KG|L)\b/i',
            ' ',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | REMOVE SPECIAL CHARACTERS
    |--------------------------------------------------------------------------
    */

        $name = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $name
        );


        /*
    |--------------------------------------------------------------------------
    | EXTRA SPACES
    |--------------------------------------------------------------------------
    */

        $name = preg_replace(
            '/\s+/',
            ' ',
            $name
        );


        return strtolower(
            trim($name)
        );
    }
    public function generateLabels(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | STEP 1 - VALIDATION
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'sender_id' => 'required|exists:label_senders,id',
        ]);


        /*
    |--------------------------------------------------------------------------
    | VARIABLES
    |--------------------------------------------------------------------------
    */

        $errors = [];

        $processedOrders = 0;
        $stockDeducted = 0;
        $salesCreated = 0;


        /*
    |--------------------------------------------------------------------------
    | STEP 2 - UNIQUE ORDER IDS
    |--------------------------------------------------------------------------
    */

        $selectedIds = collect($request->order_ids)
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();


        /*
    |--------------------------------------------------------------------------
    | STEP 3 - GET SENDER
    |--------------------------------------------------------------------------
    */

        try {

            $sender = LabelSender::findOrFail(
                $request->sender_id
            );
        } catch (\Throwable $e) {

            return back()->with(
                'label_error_report',
                [[
                    'step' => 'Sender',
                    'status' => 'FAILED',
                    'order_id' => '-',
                    'product' => '-',
                    'message' => $e->getMessage(),
                ]]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 4 - SENDER SECURITY
    |--------------------------------------------------------------------------
    */

        if (
            $this->isClient()
            &&
            (int) $sender->client_id !==
            (int) $this->clientId()
        ) {

            return back()->with(
                'label_error_report',
                [[
                    'step' => 'Sender Authorization',
                    'status' => 'FAILED',
                    'order_id' => '-',
                    'product' => '-',
                    'message' =>
                    'You are not authorized to use this sender.',
                ]]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 5 - GET ONLY PENDING ORDERS
    |--------------------------------------------------------------------------
    */

        $query = Order::whereIn(
            'id',
            $selectedIds->toArray()
        );


        /*
    |--------------------------------------------------------------------------
    | CLIENT SECURITY
    |--------------------------------------------------------------------------
    */

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }


        /*
    |--------------------------------------------------------------------------
    | ONLY PENDING ORDERS
    |--------------------------------------------------------------------------
    */

        $query->where(function ($q) {

            $q->whereNull('label_status')
                ->orWhere(
                    'label_status',
                    'pending'
                );
        });


        /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | $orders MUST BE CREATED BEFORE STEP 6
    |--------------------------------------------------------------------------
    */

        $orders = $query
            ->orderBy('id')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | CHECK MISSING / ALREADY PRINTED ORDERS
    |--------------------------------------------------------------------------
    */

        $foundIds = $orders
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            });


        $missingIds = $selectedIds->diff(
            $foundIds
        );


        foreach ($missingIds as $id) {

            $oldOrder = Order::find($id);


            if (!$oldOrder) {

                $errors[] = [
                    'step' => 'Order Check',
                    'status' => 'FAILED',
                    'order_id' => $id,
                    'product' => '-',
                    'message' => 'Order does not exist.',
                ];
            } else {

                $errors[] = [
                    'step' => 'Order Check',
                    'status' => 'SKIPPED',
                    'order_id' => $oldOrder->order_id,
                    'product' => $oldOrder->product,
                    'message' =>
                    'Order already printed, not pending, or unauthorized.',
                ];
            }
        }


        /*
    |--------------------------------------------------------------------------
    | STOP IF NOTHING PENDING
    |--------------------------------------------------------------------------
    */

        if ($orders->isEmpty()) {

            return back()->with(
                'label_error_report',
                $errors ?: [[
                    'step' => 'Order Check',
                    'status' => 'FAILED',
                    'order_id' => '-',
                    'product' => '-',
                    'message' =>
                    'No pending orders selected.',
                ]]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 6 - PRODUCT / INVENTORY MAPPING
    |--------------------------------------------------------------------------
    |
    | WAREHOUSE TYPE 1:
    |     Direct label
    |     No mapping
    |     No stock
    |     No sale
    |
    | WAREHOUSE TYPE 0:
    |     Normal inventory flow
    |
    | CLIENT 5 COMBO:
    |     Client Product ID 12
    |         ↓
    |     Inventory Product 11 + 17
    |
    |--------------------------------------------------------------------------
    */

        $productGroups = [];


        foreach ($orders as $order) {

            /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

            $client = Client::find(
                $order->client_id
            );


            if (!$client) {

                $errors[] = [
                    'step' => 'Client Check',
                    'status' => 'FAILED',
                    'order_id' => $order->order_id,
                    'product' => $order->product,
                    'message' =>
                    'Client not found. Client ID: ' .
                        $order->client_id,
                ];

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | WAREHOUSE TYPE 1
        |--------------------------------------------------------------------------
        |
        | VERY IMPORTANT:
        |
        | DO NOT DO ANY PRODUCT MAPPING.
        |
        | This order will directly go to PDF.
        |
        */

            if (
                (int) $client->warehousetype === 1
            ) {

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | QUANTITY
        |--------------------------------------------------------------------------
        */

            $quantity = (int) $order->quantity;


            if ($quantity <= 0) {

                $errors[] = [
                    'step' => 'Quantity Check',
                    'status' => 'FAILED',
                    'order_id' => $order->order_id,
                    'product' => $order->product,
                    'message' =>
                    'Invalid quantity: ' .
                        $order->quantity,
                ];

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | ORIGINAL PRODUCT NAME
        |--------------------------------------------------------------------------
        */

            $originalProductName = trim(
                (string) $order->product
            );


            /*
        |--------------------------------------------------------------------------
        | FIND CLIENT PRODUCT
        |--------------------------------------------------------------------------
        */

            $clientProduct =
                $this->findClientProduct(
                    $originalProductName
                );


            /*
        |--------------------------------------------------------------------------
        | CLIENT PRODUCT NOT FOUND
        |--------------------------------------------------------------------------
        */

            if (!$clientProduct) {

                $cleanName = preg_replace(
                    '/\s*\(Qty\s*:\s*\d+\)\s*$/i',
                    '',
                    $originalProductName
                );


                $errors[] = [
                    'step' => 'Product Mapping',
                    'status' => 'FAILED',
                    'order_id' => $order->order_id,
                    'product' => $originalProductName,
                    'message' =>
                    'Product not found in client_products table. ' .
                        'Cleaned Name: ' .
                        trim($cleanName),
                ];

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | CLIENT 5 - COMBO PRODUCT
        |--------------------------------------------------------------------------
        |
        | client_products:
        |
        | 11 = Hair Oil
        | 12 = Hair Oil + Shampoo Combo
        | 17 = Shampoo Stock
        |
        | Combo 12 uses inventory:
        |
        | 11 = Hair Oil
        | 17 = Shampoo Stock
        |
        */

            $isClient5Combo =
                (int) $order->client_id === 5
                &&
                (
                    (int) $clientProduct->id === 12
                    ||
                    (
                        stripos(
                            $originalProductName,
                            'hair oil'
                        ) !== false
                        &&
                        stripos(
                            $originalProductName,
                            'shampoo'
                        ) !== false
                    )
                );


            /*
        |--------------------------------------------------------------------------
        | CLIENT 5 COMBO PROCESSING
        |--------------------------------------------------------------------------
        */

            if ($isClient5Combo) {

                /*
            |--------------------------------------------------------------------------
            | HAIR OIL INVENTORY
            |--------------------------------------------------------------------------
            |
            | Client Product 11
            |
            */

                $hairOilQuery = Product::where(
                    'client_id',
                    5
                )
                    ->where(
                        'name',
                        11
                    );


                /*
            |--------------------------------------------------------------------------
            | SHAMPOO INVENTORY
            |--------------------------------------------------------------------------
            |
            | Client Product 17
            |
            */

                $shampooQuery = Product::where(
                    'client_id',
                    5
                )
                    ->where(
                        'name',
                        17
                    );


                $hairOilProduct = null;

                $shampooProduct = null;


                /*
            |--------------------------------------------------------------------------
            | TRY ORDER WAREHOUSE FIRST
            |--------------------------------------------------------------------------
            */

                if (
                    !empty($order->warehouse_id)
                ) {

                    $hairOilProduct =
                        (clone $hairOilQuery)
                        ->where(
                            'warehouse_id',
                            $order->warehouse_id
                        )
                        ->first();


                    $shampooProduct =
                        (clone $shampooQuery)
                        ->where(
                            'warehouse_id',
                            $order->warehouse_id
                        )
                        ->first();
                }


                /*
            |--------------------------------------------------------------------------
            | FALLBACK ANY WAREHOUSE
            |--------------------------------------------------------------------------
            */

                if (!$hairOilProduct) {

                    $hairOilProduct =
                        $hairOilQuery->first();
                }


                if (!$shampooProduct) {

                    $shampooProduct =
                        $shampooQuery->first();
                }


                /*
            |--------------------------------------------------------------------------
            | COMBO INVENTORY CHECK
            |--------------------------------------------------------------------------
            */

                if (
                    !$hairOilProduct
                    ||
                    !$shampooProduct
                ) {

                    $missing = [];


                    if (!$hairOilProduct) {

                        $missing[] =
                            'Hair Oil (Inventory Name = 11)';
                    }


                    if (!$shampooProduct) {

                        $missing[] =
                            'Shampoo Stock (Inventory Name = 17)';
                    }


                    $errors[] = [
                        'step' => 'Inventory Mapping',
                        'status' => 'FAILED',
                        'order_id' => $order->order_id,
                        'product' => $originalProductName,
                        'message' =>
                        'Combo inventory mapping not found: ' .
                            implode(', ', $missing),
                    ];


                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | HAIR OIL GROUP
            |--------------------------------------------------------------------------
            */

                $hairOilKey =
                    $hairOilProduct->id .
                    '_' .
                    $hairOilProduct->warehouse_id;


                if (
                    !isset(
                        $productGroups[$hairOilKey]
                    )
                ) {

                    $productGroups[$hairOilKey] = [

                        'product' =>
                        $hairOilProduct,

                        'client' =>
                        $client,

                        'client_product' =>
                        null,

                        'warehouse_id' =>
                        $hairOilProduct->warehouse_id,

                        'quantity' =>
                        0,

                        'amount' =>
                        0,

                        'orders' =>
                        [],
                    ];
                }


                $productGroups[$hairOilKey]['quantity']
                    += $quantity;


                $productGroups[$hairOilKey]['amount']
                    +=
                    $quantity *
                    (float) $hairOilProduct->price;


                $productGroups[$hairOilKey]['orders'][]
                    = $order;


                /*
            |--------------------------------------------------------------------------
            | SHAMPOO GROUP
            |--------------------------------------------------------------------------
            */

                $shampooKey =
                    $shampooProduct->id .
                    '_' .
                    $shampooProduct->warehouse_id;


                if (
                    !isset(
                        $productGroups[$shampooKey]
                    )
                ) {

                    $productGroups[$shampooKey] = [

                        'product' =>
                        $shampooProduct,

                        'client' =>
                        $client,

                        'client_product' =>
                        null,

                        'warehouse_id' =>
                        $shampooProduct->warehouse_id,

                        'quantity' =>
                        0,

                        'amount' =>
                        0,

                        'orders' =>
                        [],
                    ];
                }


                $productGroups[$shampooKey]['quantity']
                    += $quantity;


                $productGroups[$shampooKey]['amount']
                    +=
                    $quantity *
                    (float) $shampooProduct->price;


                $productGroups[$shampooKey]['orders'][]
                    = $order;


                /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Do not execute normal mapping for combo.
            |
            */

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | NORMAL PRODUCT INVENTORY MAPPING
        |--------------------------------------------------------------------------
        |
        | products.name = client_products.id
        |
        */

            $productQuery = Product::where(
                'client_id',
                $order->client_id
            )
                ->where(
                    'name',
                    $clientProduct->id
                );


            $product = null;


            /*
        |--------------------------------------------------------------------------
        | TRY ORDER WAREHOUSE
        |--------------------------------------------------------------------------
        */

            if (
                !empty($order->warehouse_id)
            ) {

                $product =
                    (clone $productQuery)
                    ->where(
                        'warehouse_id',
                        $order->warehouse_id
                    )
                    ->first();
            }


            /*
        |--------------------------------------------------------------------------
        | FALLBACK ANY WAREHOUSE
        |--------------------------------------------------------------------------
        */

            if (!$product) {

                $product =
                    $productQuery->first();
            }


            /*
        |--------------------------------------------------------------------------
        | INVENTORY NOT FOUND
        |--------------------------------------------------------------------------
        */

            if (!$product) {

                $errors[] = [
                    'step' => 'Inventory Mapping',
                    'status' => 'FAILED',
                    'order_id' => $order->order_id,
                    'product' => $originalProductName,
                    'message' =>
                    'Inventory product not found. ' .
                        'Client Product ID: ' .
                        $clientProduct->id .
                        ' | Client ID: ' .
                        $order->client_id,
                ];

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | NORMAL PRODUCT GROUP
        |--------------------------------------------------------------------------
        */

            $key =
                $product->id .
                '_' .
                $product->warehouse_id;


            if (
                !isset(
                    $productGroups[$key]
                )
            ) {

                $productGroups[$key] = [

                    'product' =>
                    $product,

                    'client' =>
                    $client,

                    'client_product' =>
                    $clientProduct,

                    'warehouse_id' =>
                    $product->warehouse_id,

                    'quantity' =>
                    0,

                    'amount' =>
                    0,

                    'orders' =>
                    [],
                ];
            }


            $productGroups[$key]['quantity']
                += $quantity;


            $productGroups[$key]['amount']
                +=
                $quantity *
                (float) $product->price;


            $productGroups[$key]['orders'][]
                = $order;
        }


        /*
    |--------------------------------------------------------------------------
    | STOP IF PRODUCT MAPPING ERROR
    |--------------------------------------------------------------------------
    */

        if (!empty($errors)) {

            return back()->with(
                'label_error_report',
                $errors
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 7 - PRE-CHECK STOCK
    |--------------------------------------------------------------------------
    |
    | Only warehouse type 0 products are in $productGroups.
    |
    */

        foreach (
            $productGroups as $group
        ) {

            $product =
                $group['product'];

            $client =
                $group['client'];

            $required =
                (int) $group['quantity'];


            /*
        |--------------------------------------------------------------------------
        | ONLY WAREHOUSE TYPE 0
        |--------------------------------------------------------------------------
        */

            if (
                (int) $client->warehousetype !== 0
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | AVAILABLE STOCK
        |--------------------------------------------------------------------------
        */

            $available =
                (int) $product->low_stock_alert;


            /*
        |--------------------------------------------------------------------------
        | STOCK CHECK
        |--------------------------------------------------------------------------
        */

            if (
                $available < $required
            ) {

                foreach (
                    $group['orders'] as $order
                ) {

                    $errors[] = [

                        'step' =>
                        'Stock Check',

                        'status' =>
                        'OUT_OF_STOCK',

                        'order_id' =>
                        $order->order_id,

                        'product' =>
                        $order->product,

                        'message' =>
                        'Available: ' .
                            $available .
                            ' | Required: ' .
                            $required .
                            ' | Short: ' .
                            (
                                $required -
                                $available
                            ),
                    ];
                }
            }
        }


        /*
    |--------------------------------------------------------------------------
    | STOP IF STOCK ERROR
    |--------------------------------------------------------------------------
    */

        if (!empty($errors)) {

            return back()->with(
                'label_error_report',
                $errors
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 8 - GENERATE PDF
    |--------------------------------------------------------------------------
    |
    | PDF is generated BEFORE database transaction.
    |
    */

        try {

            $pdf = Pdf::loadView(
                'inventory.labels-pdf',
                [
                    'orders' =>
                    $orders,

                    'sender' =>
                    $sender,
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


            $pdfContent =
                $pdf->output();
        } catch (\Throwable $e) {

            return back()->with(
                'label_error_report',
                [[

                    'step' =>
                    'PDF Generation',

                    'status' =>
                    'FAILED',

                    'order_id' =>
                    'Multiple',

                    'product' =>
                    '-',

                    'message' =>
                    $e->getMessage(),
                ]]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 9 - DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */

        try {

            DB::transaction(
                function () use (
                    $productGroups,
                    $orders,
                    &$stockDeducted,
                    &$salesCreated,
                    &$processedOrders
                ) {


                    /*
                |--------------------------------------------------------------------------
                | STEP 9A - LOCK ORDERS
                |--------------------------------------------------------------------------
                */

                    $lockedOrders =
                        Order::whereIn(
                            'id',
                            $orders
                                ->pluck('id')
                                ->toArray()
                        )
                        ->where(function ($q) {

                            $q->whereNull(
                                'label_status'
                            )
                                ->orWhere(
                                    'label_status',
                                    'pending'
                                );
                        })
                        ->lockForUpdate()
                        ->get();


                    /*
                |--------------------------------------------------------------------------
                | CHECK ORDER COUNT
                |--------------------------------------------------------------------------
                */

                    if (
                        $lockedOrders->count()
                        !==
                        $orders->count()
                    ) {

                        throw new \Exception(

                            'One or more selected orders are already printed ' .
                                'or are no longer pending. ' .
                                'No stock was deducted and no sale was created.'
                        );
                    }


                    /*
                |--------------------------------------------------------------------------
                | STEP 9B - STOCK DEDUCTION
                |--------------------------------------------------------------------------
                */

                    foreach (
                        $productGroups as $group
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | LOCK PRODUCT
                    |--------------------------------------------------------------------------
                    */

                        $product =
                            Product::where(
                                'id',
                                $group['product']->id
                            )
                            ->lockForUpdate()
                            ->first();


                        if (!$product) {

                            throw new \Exception(

                                'Inventory product not found. Product ID: ' .
                                    $group['product']->id
                            );
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | CLIENT
                    |--------------------------------------------------------------------------
                    */

                        $client =
                            Client::find(
                                $product->client_id
                            );


                        if (!$client) {

                            throw new \Exception(

                                'Client not found. Client ID: ' .
                                    $product->client_id
                            );
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | WAREHOUSE TYPE 1 SAFETY
                    |--------------------------------------------------------------------------
                    |
                    | This should normally never happen because
                    | type 1 orders are not added to productGroups.
                    |
                    */

                        if (
                            (int) $client->warehousetype === 1
                        ) {

                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | REQUIRED QUANTITY
                    |--------------------------------------------------------------------------
                    */

                        $quantity =
                            (int) $group['quantity'];


                        /*
                    |--------------------------------------------------------------------------
                    | CURRENT STOCK
                    |--------------------------------------------------------------------------
                    */

                        $available =
                            (int) $product->low_stock_alert;


                        /*
                    |--------------------------------------------------------------------------
                    | FINAL STOCK CHECK
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $available < $quantity
                        ) {

                            throw new \Exception(

                                'Stock changed while processing. ' .
                                    'Product ID: ' .
                                    $product->id .
                                    ' | Available: ' .
                                    $available .
                                    ' | Required: ' .
                                    $quantity
                            );
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | NEW STOCK
                    |--------------------------------------------------------------------------
                    */

                        $newStock =
                            $available -
                            $quantity;


                        /*
                    |--------------------------------------------------------------------------
                    | UPDATE STOCK
                    |--------------------------------------------------------------------------
                    */

                        $product->update([

                            'low_stock_alert' =>
                            $newStock,

                            'total_price' =>
                            $newStock *
                                (float) $product->price,

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | STOCK MOVEMENT
                    |--------------------------------------------------------------------------
                    */

                        StockMovement::create([

                            'product_id' =>
                            $product->id,

                            'quantity' =>
                            $quantity,

                            'type' =>
                            'out',

                            'price' =>
                            $product->price,

                            'movement_date' =>
                            now(),

                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | STOCK COUNTER
                    |--------------------------------------------------------------------------
                    */

                        $stockDeducted +=
                            $quantity;
                    }


                    /*
                |--------------------------------------------------------------------------
                | STEP 9C - CREATE SALES
                |--------------------------------------------------------------------------
                */

                    $salesByWarehouse = [];


                    foreach (
                        $productGroups as $group
                    ) {

                        $warehouseId =
                            $group['warehouse_id'];


                        /*
                    |--------------------------------------------------------------------------
                    | CREATE ONE SALE PER WAREHOUSE
                    |--------------------------------------------------------------------------
                    */

                        if (
                            !isset(
                                $salesByWarehouse[$warehouseId]
                            )
                        ) {

                            $sale =
                                Sale::create([

                                    'invoice_no' =>
                                    'SAL-' .
                                        now()->format(
                                            'YmdHis'
                                        ) .
                                        '-' .
                                        uniqid(),

                                    'warehouse_id' =>
                                    $warehouseId,

                                    'sale_date' =>
                                    now()->toDateString(),

                                    'total_amount' =>
                                    0,
                                ]);


                            $salesByWarehouse[$warehouseId] = $sale;


                            $salesCreated++;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | GET SALE
                    |--------------------------------------------------------------------------
                    */

                        $sale =
                            $salesByWarehouse[$warehouseId];


                        /*
                    |--------------------------------------------------------------------------
                    | SALE ITEM
                    |--------------------------------------------------------------------------
                    */

                        SaleItem::create([

                            'sale_id' =>
                            $sale->id,

                            'product_id' =>
                            $group['product']->id,

                            'quantity' =>
                            $group['quantity'],

                            'price' =>
                            $group['product']->price,

                            'subtotal' =>
                            $group['amount'],
                        ]);


                        /*
                    |--------------------------------------------------------------------------
                    | UPDATE SALE TOTAL
                    |--------------------------------------------------------------------------
                    */

                        $sale->increment(
                            'total_amount',
                            $group['amount']
                        );
                    }


                    /*
                |--------------------------------------------------------------------------
                | STEP 9D - MARK ORDERS AS PRINTED
                |--------------------------------------------------------------------------
                |
                | This applies to BOTH:
                |
                | warehousetype = 0
                | warehousetype = 1
                |
                */

                    foreach (
                        $lockedOrders as $order
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | UPDATE ONLY IF STILL PENDING
                    |--------------------------------------------------------------------------
                    */

                        $updated =
                            DB::table('orders')
                            ->where(
                                'id',
                                $order->id
                            )
                            ->where(function ($q) {

                                $q->whereNull(
                                    'label_status'
                                )
                                    ->orWhere(
                                        'label_status',
                                        'pending'
                                    );
                            })
                            ->update([

                                'label_status' =>
                                'printed',

                                'label_printed_at' =>
                                now(),

                                'label_print_count' =>
                                DB::raw(
                                    'COALESCE(label_print_count, 0) + 1'
                                ),

                                'updated_at' =>
                                now(),
                            ]);


                        /*
                    |--------------------------------------------------------------------------
                    | SAFETY CHECK
                    |--------------------------------------------------------------------------
                    */

                        if ($updated !== 1) {

                            throw new \Exception(

                                'Order ' .
                                    $order->order_id .
                                    ' could not be marked as Printed. ' .
                                    'Transaction cancelled.'
                            );
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | PROCESSED COUNTER
                    |--------------------------------------------------------------------------
                    */

                        $processedOrders++;
                    }
                }
            );
        } catch (\Throwable $e) {

            /*
        |--------------------------------------------------------------------------
        | TRANSACTION FAILED
        |--------------------------------------------------------------------------
        |
        | Everything rolls back:
        |
        | Stock
        | StockMovement
        | Sale
        | SaleItem
        | Printed status
        |
        */

            return back()->with(
                'label_error_report',
                [[

                    'step' =>
                    'Database Transaction',

                    'status' =>
                    'FAILED',

                    'order_id' =>
                    'Multiple',

                    'product' =>
                    '-',

                    'message' =>
                    $e->getMessage(),
                ]]
            );
        }


        /*
    |--------------------------------------------------------------------------
    | STEP 10 - SUCCESS REPORT
    |--------------------------------------------------------------------------
    */

        session()->flash(
            'label_success_report',
            [

                'total_orders' =>
                $orders->count(),

                'processed_orders' =>
                $processedOrders,

                'stock_deducted' =>
                $stockDeducted,

                'sales_created' =>
                $salesCreated,

                'message' =>
                'Labels generated successfully.',
            ]
        );


        /*
    |--------------------------------------------------------------------------
    | STEP 11 - PDF DOWNLOAD
    |--------------------------------------------------------------------------
    */

        $fileName =
            'shipping_labels_' .
            now()->format(
                'Y-m-d_H-i-s'
            ) .
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
    }
}
