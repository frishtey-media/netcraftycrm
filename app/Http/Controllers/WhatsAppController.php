<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Client;
use App\Models\CallingOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppController extends Controller
{


    public function webhook(Request $request)
    {
        // 🔐 VERIFY WEBHOOK
        if ($request->isMethod('get')) {
            if ($request->get('hub_verify_token') === env('WA_VERIFY_TOKEN')) {
                return response($request->get('hub_challenge'), 200);
            }
            return response('Invalid token', 403);
        }

        $data = $request->all();

        $msg = data_get($data, 'entry.0.changes.0.value.messages.0');

        if (!$msg) {
            return response()->json(['ok' => true]);
        }

        $phone = $msg['from'] ?? null;
        $text  = data_get($msg, 'text.body', 'New Lead');
        $waId  = $msg['id'] ?? null;

        $phoneNumberId = data_get($data, 'entry.0.changes.0.value.metadata.phone_number_id');

        // 🔥 CLIENT FIND (DB से, hardcode नहीं)
        $client = Client::where('phone_number_id', $phoneNumberId)->first();

        if (!$client) {
            return response()->json(['error' => 'Client not mapped'], 200);
        }

        // 🔥 EXISTING OR NEW CONVERSATION
        $conv = Conversation::firstOrCreate(
            [
                'customer_phone' => $phone,
                'client_id'      => $client->id
            ],
            [
                'assigned_to' => $this->assignStaff($client->id)
            ]
        );

        // 🔥 अगर पहले assign नहीं था → assign करो
        if (!$conv->assigned_to) {
            $conv->assigned_to = $this->assignStaff($client->id);
            $conv->save();
        }

        // 🔥 DUPLICATE MESSAGE CHECK
        if ($waId && !Message::where('wa_message_id', $waId)->exists()) {

            Message::create([
                'conversation_id' => $conv->id,
                'sender'          => 'user',
                'message'         => $text,
                'wa_message_id'   => $waId
            ]);

            $conv->update([
                'last_message'     => $text,
                'last_message_at'  => now()
            ]);
        }

        return response()->json(['ok' => true]);
    }


    public function orderCreate(Request $request)
    {
        try {

            Log::info("🔥 Webhook Hit");

            $shopDomain = $request->header('X-Shopify-Shop-Domain');

            $client = Client::where('shopify_store_url', $shopDomain)->first();

            if (!$client) {
                Log::error("❌ Client not found", ['shop' => $shopDomain]);
                return response()->json(['error' => 'Client not found'], 404);
            }

            // 🔐 Secure HMAC check (timing-safe)
            $calculatedHmac = base64_encode(hash_hmac(
                'sha256',
                $request->getContent(),
                $client->webhook_secret,
                true
            ));

            if (!hash_equals($calculatedHmac, $request->header('X-Shopify-Hmac-Sha256'))) {
                Log::error("❌ HMAC mismatch", ['client_id' => $client->id]);
                return response('Unauthorized', 401);
            }

            $data = $request->all();

            Log::info("📦 Order received", ['order' => $data]);

            $orderId = str_replace('#', '', $data['name'] ?? $data['id']);

            // ✅ Duplicate check
            if (CallingOrder::where('order_id', $orderId)
                ->where('client_id', $client->id)
                ->exists()
            ) {

                Log::info("⚠️ Duplicate order skipped", ['order_id' => $orderId]);
                return response()->json(['status' => 'duplicate']);
            }

            DB::beginTransaction();

            foreach ($data['line_items'] as $item) {

                CallingOrder::create([
                    'client_id' => $client->id,
                    'order_id' => $orderId,

                    'order_date' => $data['created_at'] ?? now(),

                    'product_name' => $item['title'] ?? 'Product',
                    'quantity' => $item['quantity'] ?? 1,

                    'customer_name' => $data['shipping_address']['name']
                        ?? ($data['customer']['first_name'] ?? 'Guest'),

                    'customer_phone' => $data['phone']
                        ?? ($data['shipping_address']['phone'] ?? ''),

                    'shipping_address' => $data['shipping_address']['address1'] ?? '',
                    'city' => $data['shipping_address']['city'] ?? '',
                    'state' => $data['shipping_address']['province'] ?? '',
                    'pincode' => $data['shipping_address']['zip'] ?? '',

                    'payment_mode' => $data['financial_status'] ?? '',
                    'amount' => $data['total_price'] ?? 0,

                    'status' => 'pending',
                ]);
            }

            DB::commit();

            Log::info("✅ Order saved successfully", [
                'order_id' => $orderId,
                'client_id' => $client->id
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error("❌ Webhook Error", [
                'message' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Server Error'], 500);
        }
    }


    // 🚀 STAFF ASSIGNMENT (CLIENT WISE)
    private function assignStaff($clientId)
    {
        $staffIds = DB::table('client_staff')
            ->where('client_id', $clientId)
            ->pluck('staff_id');

        if ($staffIds->isEmpty()) {
            return null;
        }

        // 🔥 SIMPLE RANDOM (can upgrade to round robin)
        return $staffIds->random();
    }
}
