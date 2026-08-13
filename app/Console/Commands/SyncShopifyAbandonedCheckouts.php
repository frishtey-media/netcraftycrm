<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\callingorder;
use GuzzleHttp\Client as GuzzleClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncShopifyAbandonedCheckouts extends Command
{
    protected $signature = 'sync:shopify-abandoned-checkouts';

    protected $description = 'Fetch Shopify Abandoned Checkouts and Save to CRM';


    private function normalizePhone($phone)
    {
        if (empty($phone)) {
            return '';
        }


        $phone = preg_replace('/[^0-9]/', '', (string) $phone);


        if (preg_match('/^91\d{10}$/', $phone)) {
            return substr($phone, 2);
        }

        // Starts with 0
        if (preg_match('/^0\d{10}$/', $phone)) {
            return substr($phone, 1);
        }

        // Already 10 digits
        if (preg_match('/^\d{10}$/', $phone)) {
            return $phone;
        }

        // Fallback - last 10 digits
        if (strlen($phone) > 10) {
            return substr($phone, -10);
        }

        return $phone;
    }


    private function getShopifyApiVersion($clientId)
    {
        if ($clientId == 11) {
            return '2026-04';
        }

        if ($clientId == 13) {
            return '2026-01';
        }

        return '2025-10';
    }

    public function handle()
    {
        Log::info("🚀 Shopify Abandoned Checkout Sync START: " . now());

        // $clients = Client::all();
        $clients = Client::where('id', 5)->get();

        foreach ($clients as $client) {

            Log::info(
                "➡️ Abandoned Checkout Client: {$client->id} | {$client->shopify_store_url}"
            );

            if (
                empty($client->shopify_store_url) ||
                empty($client->shopify_access_token)
            ) {
                Log::error(
                    "❌ Missing Shopify credentials for client ID: {$client->id}"
                );

                continue;
            }



            $startIST = Carbon::now('Asia/Kolkata')->subDays(30);
            $endIST   = Carbon::now('Asia/Kolkata');

            $start = $startIST->copy()->utc()->toIso8601String();
            $end   = $endIST->copy()->utc()->toIso8601String();



            $apiVersion = $this->getShopifyApiVersion($client->id);

            $url = "https://{$client->shopify_store_url}/admin/api/{$apiVersion}/graphql.json";


            $query = <<<'GRAPHQL'
query AbandonedCheckouts($first: Int!, $after: String, $queryFilter: String!) {
    abandonedCheckouts(
        first: $first
        after: $after
        query: $queryFilter
        sortKey: CREATED_AT
        reverse: false
    ) {
        nodes {
            id
            name
            abandonedCheckoutUrl
            createdAt
            updatedAt
            completedAt

            customer {
                id
                firstName
                lastName
                email
                phone
            }

            shippingAddress {
                firstName
                lastName
                name
                company
                address1
                address2
                city
                province
                zip
                country
                phone
            }

            billingAddress {
                firstName
                lastName
                name
                company
                address1
                address2
                city
                province
                zip
                country
                phone
            }

            customAttributes {
                key
                value
            }

            discountCodes

            totalPriceSet {
                shopMoney {
                    amount
                    currencyCode
                }
            }

            lineItems(first: 100) {
                nodes {
                    id
                    title
                    variantTitle
                    sku
                    quantity

                    originalUnitPriceSet {
                        shopMoney {
                            amount
                            currencyCode
                        }
                    }

                    discountedTotalPriceSet {
                        shopMoney {
                            amount
                            currencyCode
                        }
                    }
                }
            }
        }

        pageInfo {
            hasNextPage
            endCursor
        }
    }
}
GRAPHQL;



            $queryFilter =
                'created_at:>=' . $start .
                ' created_at:<=' . $end .
                ' recovery_state:not_recovered';

            $after = null;

            $totalFetched = 0;
            $totalInserted = 0;
            $totalSkipped = 0;

            try {

                $http = new GuzzleClient([
                    'timeout' => 60,
                    'connect_timeout' => 20,
                ]);

                do {

                    $response = $http->post($url, [
                        'headers' => [
                            'X-Shopify-Access-Token' =>
                            $client->shopify_access_token,

                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],

                        'json' => [
                            'query' => $query,

                            'variables' => [
                                'first' => 100,
                                'after' => $after,
                                'queryFilter' => $queryFilter,
                            ],
                        ],
                    ]);

                    $data = json_decode(
                        $response->getBody()->getContents(),
                        true
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Shopify HTTP / GraphQL Errors
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($data['errors'])) {

                        Log::error(
                            "❌ Shopify GraphQL Error Client {$client->id}",
                            [
                                'errors' => $data['errors']
                            ]
                        );

                        $this->error(
                            "Shopify GraphQL error for Client {$client->id}"
                        );

                        break;
                    }

                    $connection =
                        $data['data']['abandonedCheckouts']
                        ?? null;

                    if (!$connection) {

                        Log::error(
                            "❌ Invalid abandonedCheckouts response Client {$client->id}",
                            [
                                'response' => $data
                            ]
                        );

                        break;
                    }

                    $checkouts = $connection['nodes'] ?? [];

                    $totalFetched += count($checkouts);

                    Log::info(
                        "📦 Abandoned Checkouts fetched: "
                            . count($checkouts)
                            . " | Client: {$client->id}"
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Process Each Checkout
                    |--------------------------------------------------------------------------
                    */

                    foreach ($checkouts as $checkout) {

                        $checkoutId = $checkout['id'] ?? '';

                        if (empty($checkoutId)) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Duplicate Check
                        |--------------------------------------------------------------------------
                        */

                        $exists = CallingOrder::where(
                            'client_id',
                            $client->id
                        )
                            ->where(
                                'shopify_checkout_id',
                                $checkoutId
                            )
                            ->exists();

                        if ($exists) {

                            $totalSkipped++;

                            Log::info(
                                "⏭️ Checkout already exists: "
                                    . $checkoutId
                            );

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Customer
                        |--------------------------------------------------------------------------
                        */

                        $customer = $checkout['customer'] ?? [];

                        $customerFirstName =
                            trim($customer['firstName'] ?? '');

                        $customerLastName =
                            trim($customer['lastName'] ?? '');

                        $customerName =
                            trim(
                                $customerFirstName .
                                    ' ' .
                                    $customerLastName
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Shipping Address
                        |--------------------------------------------------------------------------
                        */

                        $shipping =
                            $checkout['shippingAddress']
                            ?? [];

                        $billing =
                            $checkout['billingAddress']
                            ?? [];

                        /*
                        |--------------------------------------------------------------------------
                        | Phone Priority
                        |--------------------------------------------------------------------------
                        |
                        | 1. Shipping phone
                        | 2. Customer phone
                        | 3. Billing phone
                        |
                        */

                        $rawPhone =
                            $shipping['phone']
                            ?? ($customer['phone']
                                ?? ($billing['phone'] ?? ''));

                        $customerPhone =
                            $this->normalizePhone($rawPhone);

                        /*
                        |--------------------------------------------------------------------------
                        | Custom Attributes
                        |--------------------------------------------------------------------------
                        */

                        $customFullName = '';
                        $customMobile = '';
                        $noteAddress = [];

                        foreach (
                            ($checkout['customAttributes'] ?? [])
                            as $attribute
                        ) {

                            $key =
                                strtolower(
                                    trim($attribute['key'] ?? '')
                                );

                            $value =
                                trim(
                                    $attribute['value'] ?? ''
                                );

                            if (
                                in_array(
                                    $key,
                                    [
                                        'full name',
                                        'fullname',
                                        'name'
                                    ]
                                )
                            ) {
                                $customFullName = $value;
                            }

                            if (
                                in_array(
                                    $key,
                                    [
                                        'mobile number',
                                        'mobile',
                                        'phone',
                                        'phone number'
                                    ]
                                )
                            ) {
                                $customMobile = $value;
                            }

                            if (
                                in_array(
                                    $key,
                                    [
                                        'complete full address',
                                        'address',
                                        'landmark',
                                        'landmark (famous spot nearby)'
                                    ]
                                )
                            ) {
                                $noteAddress[] = $value;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Custom Mobile Has Priority
                        |--------------------------------------------------------------------------
                        */

                        if (!empty($customMobile)) {
                            $customerPhone =
                                $this->normalizePhone(
                                    $customMobile
                                );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Customer Name
                        |--------------------------------------------------------------------------
                        */

                        if (!empty($customFullName)) {
                            $customerName = $customFullName;
                        }

                        if (empty($customerName)) {

                            $customerName =
                                trim(
                                    ($shipping['name'] ?? '')
                                );
                        }

                        if (empty($customerName)) {
                            $customerName = 'Guest';
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Address
                        |--------------------------------------------------------------------------
                        */

                        $fullAddress =
                            implode(
                                ', ',
                                array_filter(
                                    [
                                        $shipping['address1']
                                            ?? null,

                                        $shipping['address2']
                                            ?? null,

                                        $shipping['company']
                                            ?? null,

                                        implode(
                                            ', ',
                                            $noteAddress
                                        ),
                                    ]
                                )
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Product Names
                        |--------------------------------------------------------------------------
                        */

                        $productNames = [];
                        $totalQty = 0;

                        foreach (
                            ($checkout['lineItems']['nodes'] ?? [])
                            as $item
                        ) {

                            $title =
                                $item['title']
                                ?? 'Product';

                            $variantTitle =
                                $item['variantTitle']
                                ?? '';

                            $quantity =
                                (int) (
                                    $item['quantity']
                                    ?? 1
                                );

                            $sku =
                                $item['sku']
                                ?? '';

                            $productText = $title;

                            if (!empty($variantTitle)) {
                                $productText .=
                                    ' - ' .
                                    $variantTitle;
                            }

                            if (!empty($sku)) {
                                $productText .=
                                    ' [SKU: ' .
                                    $sku .
                                    ']';
                            }

                            $productText .=
                                ' (Qty: ' .
                                $quantity .
                                ')';

                            $productNames[] =
                                $productText;

                            $totalQty += $quantity;
                        }

                        $productNames =
                            implode(
                                ', ',
                                $productNames
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Checkout Number
                        |--------------------------------------------------------------------------
                        */

                        $checkoutName =
                            $checkout['name']
                            ?? '';

                        $orderNumber =
                            str_replace(
                                '#',
                                '',
                                $checkoutName
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Amount
                        |--------------------------------------------------------------------------
                        */

                        $amount =
                            $checkout['totalPriceSet']['shopMoney']['amount']
                            ?? 0;

                        /*
                        |--------------------------------------------------------------------------
                        | Date
                        |--------------------------------------------------------------------------
                        */

                        $orderDate =
                            !empty($checkout['createdAt'])
                            ? Carbon::parse(
                                $checkout['createdAt']
                            )
                            : now();

                        /*
                        |--------------------------------------------------------------------------
                        | Save to CallingOrder
                        |--------------------------------------------------------------------------
                        */

                        CallingOrder::create([

                            'client_id' =>
                            $client->id,

                            /*
                             * Example:
                             * AC-123456
                             */
                            'order_id' =>
                            $orderNumber
                                ?: 'AC-' .
                                substr(
                                    md5($checkoutId),
                                    0,
                                    10
                                ),

                            /*
                             * Keep NULL because this is NOT
                             * an actual Shopify order.
                             */
                            'shopify_order_id' => null,

                            'shopify_checkout_id' =>
                            $checkoutId,

                            'checkout_url' =>
                            $checkout['abandonedCheckoutUrl'] ?? null,

                            'order_date' =>
                            $orderDate,

                            'product_name' =>
                            $productNames,

                            'shopify_product_name' =>
                            $productNames,

                            'quantity' =>
                            $totalQty,

                            'customer_name' =>
                            $customerName,

                            'customer_phone' =>
                            $customerPhone,

                            'customer_email' =>
                            $customer['email']
                                ?? null,

                            'father_name' =>
                            $shipping['company']
                                ?? '',

                            'shipping_address' =>
                            $fullAddress,

                            'city' =>
                            $shipping['city']
                                ?? '',

                            'state' =>
                            $shipping['province']
                                ?? '',

                            'pincode' =>
                            $shipping['zip']
                                ?? '',

                            /*
                             * Abandoned checkout has
                             * no payment yet.
                             */
                            'payment_mode' =>
                            'abandoned_checkout',

                            'amount' =>
                            $amount,

                            /*
                             * Your existing enum allows
                             * pending.
                             */
                            'status' =>
                            'pending',

                            'remarks' =>
                            'Shopify Abandoned Checkout',

                            /*
                             * Important for CRM filtering
                             */
                            'order_source' =>
                            'shopify_abandoned_checkout',

                            'is_exported' =>
                            0,
                        ]);

                        $totalInserted++;

                        Log::info(
                            "✅ Abandoned Checkout Saved",
                            [
                                'client_id' =>
                                $client->id,

                                'checkout_id' =>
                                $checkoutId,

                                'name' =>
                                $customerName,

                                'phone' =>
                                $customerPhone,

                                'amount' =>
                                $amount,
                            ]
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Pagination
                    |--------------------------------------------------------------------------
                    */

                    $pageInfo =
                        $connection['pageInfo']
                        ?? [];

                    $hasNextPage =
                        $pageInfo['hasNextPage']
                        ?? false;

                    $after =
                        $pageInfo['endCursor']
                        ?? null;
                } while (
                    $hasNextPage &&
                    !empty($after)
                );

                Log::info(
                    "📊 Client {$client->id} Abandoned Checkout Summary",
                    [
                        'fetched' =>
                        $totalFetched,

                        'inserted' =>
                        $totalInserted,

                        'skipped' =>
                        $totalSkipped,
                    ]
                );
            } catch (\Throwable $e) {

                Log::error(
                    "❌ Shopify Abandoned Checkout Error "
                        . "(Client {$client->id})",
                    [
                        'message' =>
                        $e->getMessage(),

                        'file' =>
                        $e->getFile(),

                        'line' =>
                        $e->getLine(),
                    ]
                );

                $this->error(
                    "Client {$client->id}: "
                        . $e->getMessage()
                );

                continue;
            }
        }

        Log::info(
            "✅ Shopify Abandoned Checkout Sync END: "
                . now()
        );

        $this->info(
            'Abandoned Checkouts Synced Successfully'
        );

        return Command::SUCCESS;
    }
}
