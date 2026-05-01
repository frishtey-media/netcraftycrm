<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

class StaffChatController extends Controller
{
    public function inbox()
    {
        $staffId = Auth::guard('calling_user')->id();

        $conversations = Conversation::where('assigned_to', $staffId)->get();

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
        $request->validate([
            'conversation_id' => 'required',
            'message' => 'required'
        ]);

        $conv = Conversation::findOrFail($request->conversation_id);

        // 🔥 SEND TO WHATSAPP
        $client = new Client();

        $client->post("https://graph.facebook.com/v19.0/" . env('WA_PHONE_ID') . "/messages", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('WA_TOKEN'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                "messaging_product" => "whatsapp",
                "to" => $conv->customer_phone,
                "type" => "text",
                "text" => [
                    "body" => $request->message
                ]
            ]
        ]);

        // 🔥 SAVE MESSAGE
        Message::create([
            'conversation_id' => $conv->id,
            'sender' => 'staff',
            'message' => $request->message
        ]);

        // 🔥 UPDATE LAST MESSAGE
        $conv->update([
            'last_message' => $request->message,
            'last_message_at' => now()
        ]);

        return back()->with('success', 'Message Sent');
    }
}
