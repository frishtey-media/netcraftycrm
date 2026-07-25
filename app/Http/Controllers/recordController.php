<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\records;
use App\Models\Barcode;
use App\Models\ShopifyOrder;
use Illuminate\Support\Facades\DB;
use App\Models\ClientProduct;
use App\Models\Client;
use App\Models\callingorder;
use App\Models\CallingUser;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BulkOrderImport;
use Carbon\Carbon;

class RecordController extends Controller
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


    public function create()
    {
        $user = auth()->user();

        // Super Admin => All Clients
        if ($user->role == 'super_admin') {
            $clients = Client::orderBy('client_name')->get();
        } else {
            $clients = Client::where('id', $user->client_id)->get();
        }

        $staffs = CallingUser::where('status', 1)->get();

        // Temporary Order ID
        $orderId = 'AUTO-GENERATED';

        return view(
            'record.create',
            compact(
                'orderId',
                'clients',
                'staffs'
            )
        );
    }
    public function generateOrderId(Request $request, $staffId)
    {
        $staff = CallingUser::findOrFail($staffId);

        $selectedDate = \Carbon\Carbon::parse($request->created_at);

        $shortName = strtoupper(substr($staff->name, 0, 1))
            . strtolower(substr($staff->name, -1));

        $date = $selectedDate->format('d-m-y');

        $count = CallingOrder::where('assigned_to', $staff->id)
            ->whereDate('created_at', $selectedDate)
            ->count() + 1;

        return response()->json([
            'order_id' => $shortName . '-' . $date . '-' . $count
        ]);
    }
    public function store(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Common Validation
    |--------------------------------------------------------------------------
    */

        $request->validate([

            'client_id' => 'required|exists:clients,id',

            'assigned_to' => 'required',

            'created_at' => 'required|date',

            'customer_phone' => 'required',

            'status' => 'required|in:verified,pending,not_reachable,same_order,cancel',

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
                    'Invalid customer mobile number.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */

        $staff = CallingUser::findOrFail(
            $request->assigned_to
        );


        /*
    |--------------------------------------------------------------------------
    | Generate Order ID
    |--------------------------------------------------------------------------
    */

        $selectedDate = Carbon::parse(
            $request->created_at
        );


        $name = trim($staff->name);


        $shortName =
            strtoupper(substr($name, 0, 1))
            .
            strtolower(substr($name, -1));


        $date = $selectedDate->format('d-m-y');


        $count = CallingOrder::where(
            'assigned_to',
            $staff->id
        )
            ->whereDate(
                'created_at',
                $selectedDate
            )
            ->count() + 1;


        $orderId =
            $shortName
            . '-'
            . $date
            . '-'
            . $count;


        /*
    |--------------------------------------------------------------------------
    | NON VERIFIED LEAD
    |--------------------------------------------------------------------------
    */

        if ($request->status !== 'verified') {

            $request->validate([

                'remarks' => 'required|string|max:1000',

            ]);


            CallingOrder::create([

                'client_id' => $request->client_id,

                'assigned_to' => $request->assigned_to,

                'order_id' => $orderId,

                'order_date' => $selectedDate,

                'customer_phone' => $phone,

                'status' => $request->status,

                'remarks' => $request->remarks,

                'order_source' => 'whatsapp',

                'created_at' => $selectedDate,

                'updated_at' => $selectedDate,

            ]);


            return back()->with(

                'success',

                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $request->status
                    )
                )
                    . ' lead saved successfully.'

            );
        }


        /*
    |--------------------------------------------------------------------------
    | VERIFIED VALIDATION
    |--------------------------------------------------------------------------
    */

        $request->validate([

            'products' => 'required|array|min:1',

            'products.*.product' =>
            'required|string',

            'products.*.quantity' =>
            'required|integer|min:1',

            'customer_name' =>
            'required|string',

            'payment_mode' =>
            'required',

            'age' =>
            'required',

            'amount' =>
            'required|numeric',

            'shipping_pincode' =>
            'required',

            'shipping_address_line1' =>
            'required',

        ]);


        /*
    |--------------------------------------------------------------------------
    | Product Calculation
    |--------------------------------------------------------------------------
    */

        $productNames = [];

        $totalQty = 0;

        $totalWeight = 0;


        foreach ($request->products as $product) {

            $productName =
                trim($product['product']);


            $qty =
                (int) $product['quantity'];


            $weight =
                (float) ($product['weight'] ?? 0);


            $productNames[] =
                $productName
                . ' (Qty: '
                . $qty
                . ')';


            $totalQty += $qty;


            $totalWeight +=
                ($qty * $weight);
        }


        /*
    |--------------------------------------------------------------------------
    | VERIFIED ORDER SAVE
    |--------------------------------------------------------------------------
    */

        CallingOrder::create([

            'client_id' =>
            $request->client_id,

            'assigned_to' =>
            $request->assigned_to,

            'order_id' =>
            $orderId,

            'order_date' =>
            $selectedDate,

            'product_name' =>
            implode(', ', $productNames),

            'shopify_product_name' =>
            implode(', ', $productNames),

            'quantity' =>
            $totalQty,

            'weight' =>
            $totalWeight,

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
            trim(
                ($request->shipping_address_line1 ?? '')
                    . ' '
                    . ($request->shipping_address_line2 ?? '')
            ),

            'city' =>
            $request->city,

            'state' =>
            $request->state,

            'pincode' =>
            $request->shipping_pincode,

            'payment_mode' =>
            $request->payment_mode,

            'amount' =>
            $request->amount,

            'status' =>
            'verified',

            'remarks' =>
            $request->verified_remarks,

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
            ->get();

        return response()->json($products);
    }



    public function whstappimportOrders(Request $request)
    {
        $request->validate([
            'client_id'   => 'required',
            'assigned_to' => 'required',
            'created_at'  => 'required|date',
            'file'        => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(
            new BulkOrderImport(
                $request->client_id,
                $request->assigned_to,
                $request->created_at
            ),
            $request->file('file')
        );

        return back()->with(
            'success',
            'Orders Imported Successfully'
        );
    }
}
