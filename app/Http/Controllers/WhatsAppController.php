<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\conversation;
use App\Models\Message;
use App\Models\Client;
use App\Models\callingorder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WhatsAppController extends Controller
{


    public function webhook(Request $request, $clientId)
    {
        Log::info('========== WHATSAPP WEBHOOK HIT ==========');

        // ==========================
        // WEBHOOK VERIFY (GET)
        // ==========================
        if ($request->isMethod('get')) {

            $mode = $request->query('hub_mode')
                ?? $request->query('hub.mode');

            $token = $request->query('hub_verify_token')
                ?? $request->query('hub.verify_token');

            $challenge = $request->query('hub_challenge')
                ?? $request->query('hub.challenge');

            $client = Client::find($clientId);

            Log::info('WEBHOOK VERIFY', [
                'client_id' => $clientId,
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
                'db_token' => $client?->webhook_secret
            ]);

            if (!$client) {
                Log::error('CLIENT NOT FOUND');
                return response('Client Not Found', 404);
            }

            if (
                $mode === 'subscribe' &&
                trim($token) === trim($client->webhook_secret)
            ) {

                Log::info('WEBHOOK VERIFIED SUCCESSFULLY');

                return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
            }

            Log::error('INVALID VERIFY TOKEN');

            return response('Invalid token', 403);
        }

        // ==========================
        // INCOMING POST WEBHOOK
        // ==========================

        Log::info('POST PAYLOAD RECEIVED', [
            'payload' => $request->all()
        ]);

        $data = $request->all();

        // Status update
        if (data_get($data, 'entry.0.changes.0.value.statuses')) {

            Log::info('STATUS CALLBACK RECEIVED');

            return response()->json([
                'success' => true
            ]);
        }

        $phoneNumberId = data_get(
            $data,
            'entry.0.changes.0.value.metadata.phone_number_id'
        );

        Log::info('PHONE NUMBER ID RECEIVED', [
            'phone_number_id' => $phoneNumberId
        ]);

        $client = Client::where(
            'phone_number_id',
            $phoneNumberId
        )->first();

        if (!$client) {

            Log::error('CLIENT MAPPING FAILED', [
                'phone_number_id' => $phoneNumberId
            ]);

            return response()->json([
                'error' => 'Client not mapped'
            ]);
        }

        Log::info('CLIENT FOUND', [
            'client_id' => $client->id,
            'client_name' => $client->client_name
        ]);

        $msg = data_get(
            $data,
            'entry.0.changes.0.value.messages.0'
        );

        if (!$msg) {

            Log::warning('MESSAGE OBJECT NOT FOUND');

            return response()->json([
                'success' => true
            ]);
        }

        $phone = $msg['from'] ?? null;
        $waId = $msg['id'] ?? null;
        $type = $msg['type'] ?? 'text';

        $text = match ($type) {
            'text' => data_get($msg, 'text.body'),
            'image' => '📷 Image',
            'document' => '📄 Document',
            'audio' => '🎤 Audio',
            'video' => '🎥 Video',
            default => 'New Message'
        };

        Log::info('MESSAGE RECEIVED', [
            'phone' => $phone,
            'wa_id' => $waId,
            'type' => $type,
            'message' => $text
        ]);

        $staffId = $this->assignStaff($client->id);

        Log::info('STAFF ASSIGNED', [
            'staff_id' => $staffId
        ]);

        $conversation = Conversation::firstOrCreate(
            [
                'customer_phone' => $phone,
                'client_id' => $client->id
            ],
            [
                'assigned_to' => $staffId,
                'status' => 'open',
                'last_message' => $text,
                'last_message_at' => now()
            ]
        );

        Log::info('CONVERSATION CREATED/FOUND', [
            'conversation_id' => $conversation->id
        ]);

        if (
            $waId &&
            !Message::where('wa_message_id', $waId)->exists()
        ) {

            Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'user',
                'message' => $text,
                'wa_message_id' => $waId
            ]);

            Log::info('MESSAGE SAVED');

            $conversation->update([
                'last_message' => $text,
                'last_message_at' => now(),
                'status' => 'open'
            ]);
        }

        return response()->json([
            'success' => true
        ]);
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
