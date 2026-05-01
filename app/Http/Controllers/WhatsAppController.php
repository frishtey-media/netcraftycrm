<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Client;
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
