<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\conversation;
use App\Models\Message;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class StaffChatController extends Controller
{
    public function inbox()
    {
        $staffId = Auth::guard('calling_user')->id();

        $conversations = Conversation::where('assigned_to', $staffId)
            ->latest()
            ->get();

        return view('calling.inbox', compact('conversations'));
    }

    public function chat($id)
    {
        $conversation = Conversation::findOrFail($id);

        $messages = Message::where('conversation_id', $id)
            ->orderBy('id')
            ->get();

        return view('calling.chat', compact('conversation', 'messages'));
    }

    public function send(Request $request)
    {

        //dd('SEND METHOD HIT');

        $request->validate([
            'conversation_id' => 'required',
            'message' => 'required'
        ]);

        try {

            $conv = Conversation::findOrFail($request->conversation_id);

            $clientData = Client::find($conv->client_id);

            if (!$clientData) {
                return back()->with('error', 'Client not found');
            }

            if (empty($clientData->phone_number_id)) {
                return back()->with('error', 'Phone Number ID missing');
            }

            if (empty($clientData->access_token)) {
                return back()->with('error', 'WhatsApp Access Token missing');
            }

            $phone = preg_replace('/[^0-9]/', '', $conv->customer_phone);

            Log::info('WHATSAPP SEND START', [
                'client_id' => $clientData->id,
                'phone_number_id' => $clientData->phone_number_id,
                'customer_phone' => $phone
            ]);

            $http = new GuzzleClient();

            $response = $http->post(
                "https://graph.facebook.com/v23.0/{$clientData->phone_number_id}/messages",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $clientData->access_token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'messaging_product' => 'whatsapp',
                        'to' => $phone,
                        'type' => 'text',
                        'text' => [
                            'body' => $request->message
                        ]
                    ]
                ]
            );

            Log::info('WHATSAPP RESPONSE', [
                'response' => json_decode(
                    $response->getBody()->getContents(),
                    true
                )
            ]);

            Message::create([
                'conversation_id' => $conv->id,
                'sender' => 'staff',
                'message' => $request->message
            ]);

            $conv->update([
                'last_message' => $request->message,
                'last_message_at' => now()
            ]);

            return back()->with('success', 'Message Sent Successfully');
        } catch (\Exception $e) {

            Log::error('WHATSAPP SEND ERROR', [
                'message' => $e->getMessage()
            ]);

            return back()->with(
                'error',
                'WhatsApp Error: ' . $e->getMessage()
            );
        }
    }
}
