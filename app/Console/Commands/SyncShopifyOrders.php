<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\CallingOrder;
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

            // ❌ Skip invalid client
            if (empty($client->shopify_store_url) || empty($client->shopify_access_token)) {
                Log::error("❌ Missing credentials for client ID: {$client->id}");
                continue;
            }

            // 🔥 FIX: Wider range (avoid missing orders)
            $start = Carbon::now()->subDays(3)->startOfDay()->toIso8601String();
            $end   = Carbon::now()->toIso8601String();

            $url = "https://{$client->shopify_store_url}/admin/api/2024-04/orders.json?status=any&limit=250&created_at_min={$start}&created_at_max={$end}";

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

                    foreach ($order['line_items'] as $item) {

                        $orderId = isset($order['name'])
                            ? str_replace('#', '', $order['name'])
                            : $order['id'];

                        // 🔥 DUPLICATE FIX (better check)
                        $exists = CallingOrder::where('order_id', $orderId)
                            ->where('client_id', $client->id)
                            ->exists();

                        if ($exists) continue;

                        CallingOrder::create([
                            'client_id' => $client->id,

                            'order_id' => $orderId,

                            'order_date' => isset($order['created_at'])
                                ? Carbon::parse($order['created_at'])
                                : now(),

                            'product_name' => $item['title'] ?? 'Product',
                            'quantity' => $item['quantity'] ?? 1,

                            'customer_name' => $order['shipping_address']['name']
                                ?? ($order['customer']['first_name'] ?? 'Guest'),

                            'father_name' => $order['shipping_address']['company'] ?? '',

                            'customer_phone' => $order['phone']
                                ?? ($order['shipping_address']['phone'] ?? ''),

                            'shipping_address' => $order['shipping_address']['address1'] ?? '',
                            'city' => $order['shipping_address']['city'] ?? '',
                            'state' => $order['shipping_address']['province'] ?? '',
                            'pincode' => $order['shipping_address']['zip'] ?? '',

                            'payment_mode' => $order['financial_status'] ?? '',
                            'amount' => $order['total_price'] ?? 0,

                            'status' => 'pending',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("❌ Shopify Error (Client {$client->id}): " . $e->getMessage());
                continue;
            }
        }

        Log::info("✅ Shopify Sync END: " . now());

        $this->info('Orders Synced Successfully');
    }
}
