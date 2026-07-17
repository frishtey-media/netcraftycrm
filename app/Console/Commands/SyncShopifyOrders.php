<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\callingorder;
use GuzzleHttp\Client as GuzzleClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncShopifyOrders extends Command
{
    protected $signature = 'sync:shopify-orders';
    protected $description = 'Fetch Shopify Orders and Save to DB';

    public function handle()
    {
        Log::info("🚀 Shopify Sync START: " . now());

        $clients = Client::all();

        foreach ($clients as $client) {

            Log::info("➡️ Client: {$client->id} | {$client->shopify_store_url}");

            if (empty($client->shopify_store_url) || empty($client->shopify_access_token)) {
                Log::error("❌ Missing credentials for client ID: {$client->id}");
                continue;
            }

            $startIST = Carbon::yesterday('Asia/Kolkata')->setTime(17, 0, 0);
            $endIST   = Carbon::now('Asia/Kolkata');

            $start = $startIST->copy()->utc()->toIso8601String();
            $end   = $endIST->copy()->utc()->toIso8601String();

            //$url = "https://{$client->shopify_store_url}/admin/api/2024-04/orders.json?status=any&limit=250&created_at_min={$start}&created_at_max={$end}";


            if ($client->id == 11) {

                $url = "https://{$client->shopify_store_url}/admin/api/2026-04/orders.json?status=any&limit=250&created_at_min={$start}&created_at_max={$end}";
            } elseif ($client->id == 13) {

                $url = "https://{$client->shopify_store_url}/admin/api/2026-01/orders.json?status=any&limit=250&created_at_min={$start}&created_at_max={$end}";
            } else {

                $url = "https://{$client->shopify_store_url}/admin/api/2025-10/orders.json?status=any&limit=250&created_at_min={$start}&created_at_max={$end}";
            }

            try {

                $http = new GuzzleClient([
                    'timeout' => 30,
                ]);

                $response = $http->get($url, [
                    'headers' => [
                        'X-Shopify-Access-Token' => $client->shopify_access_token,
                        'Accept' => 'application/json'
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                $orders = $data['orders'] ?? [];

                Log::info("📦 Orders fetched: " . count($orders));

                foreach ($orders as $order) {

                    // -----------------------------
                    // Read Note Attributes
                    // -----------------------------
                    $fullName = '';
                    $mobile = '';
                    $noteAddress = [];

                    foreach (($order['note_attributes'] ?? []) as $attr) {

                        $name = strtolower(trim($attr['name']));
                        $value = trim($attr['value'] ?? '');

                        if ($name === 'full name') {
                            $fullName = $value;
                        }

                        if ($name === 'mobile number') {
                            $mobile = $value;
                        }

                        if (
                            in_array($name, [
                                'complete full address',
                                'landmark (famous spot nearby)',
                                'address',
                                'landmark'
                            ])
                        ) {
                            $noteAddress[] = $value;
                        }
                    }

                    // -----------------------------
                    // Build Full Address
                    // -----------------------------
                    $fullAddress = implode(', ', array_filter([
                        $order['shipping_address']['address1'] ?? null,
                        $order['shipping_address']['address2'] ?? null,
                        $order['shipping_address']['company'] ?? null,
                        implode(', ', $noteAddress),
                    ]));

                    $orderNumber = isset($order['name'])
                        ? str_replace('#', '', $order['name'])
                        : '';

                    $shopifyOrderId = $order['id'];

                    $exists = CallingOrder::where(
                        'shopify_order_id',
                        $shopifyOrderId
                    )
                        ->where('client_id', $client->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    /*
|--------------------------------------------------------------------------
| Collect All Products
|--------------------------------------------------------------------------
*/
                    $productNames = [];
                    $totalQty = 0;

                    foreach ($order['line_items'] as $item) {

                        $productNames[] =
                            ($item['title'] ?? 'Product')
                            . ' (Qty: ' . ($item['quantity'] ?? 1) . ')';

                        $totalQty += ($item['quantity'] ?? 1);
                    }

                    $productNames = implode(', ', $productNames);

                    /*
|--------------------------------------------------------------------------
| Save Single Record Per Order
|--------------------------------------------------------------------------
*/
                    CallingOrder::create([

                        'client_id' => $client->id,

                        'order_id' => $orderNumber,
                        'shopify_order_id' => $shopifyOrderId,
                        'order_date' => isset($order['created_at'])
                            ? Carbon::parse($order['created_at'])
                            : now(),

                        'product_name' => $productNames,

                        'quantity' => $totalQty,

                        'customer_name' => $fullName
                            ?: ($order['shipping_address']['name']
                                ?? ($order['customer']['first_name'] ?? 'Guest')),

                        'father_name' => $order['shipping_address']['company'] ?? '',

                        'customer_phone' => $mobile
                            ?: ($order['phone']
                                ?? ($order['shipping_address']['phone'] ?? '')),

                        'shipping_address' => $fullAddress,

                        'city' => $order['shipping_address']['city'] ?? '',

                        'state' => $order['shipping_address']['province'] ?? '',

                        'pincode' => $order['shipping_address']['zip'] ?? '',

                        'payment_mode' => $order['financial_status'] ?? '',

                        'amount' => $order['total_price'] ?? 0,

                        'status' => 'pending',
                    ]);
                }
            } catch (\Exception $e) {

                Log::error(
                    "❌ Shopify Error (Client {$client->id}): "
                        . $e->getMessage()
                );

                continue;
            }
        }

        Log::info("✅ Shopify Sync END: " . now());

        $this->info('Orders Synced Successfully');
    }
}
