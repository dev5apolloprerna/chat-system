<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Client;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\WhatsAppAccount;

class ChatController extends Controller
{
    // Chat dashboard (all conversations for logged in client)
    public function index()
    {
        $user = Auth::guard('client')->user();

        $conversations = DB::table('messages')
            ->selectRaw('MAX(id) as id, sender_number,sender_name')
            ->where('receiver_number', $user->whatsapp_meta_no)
            ->groupBy('sender_number', 'sender_name')
            ->orderByDesc(DB::raw('MAX(id)'))
            ->get();

        return view('client.chat.index', compact('conversations'));
    }

    // Open single conversation
    public function show($id)
    {
        $conversation = Conversation::where('id', $id)
            ->where('customer_id', Auth::guard('client')->id())
            ->firstOrFail();

        return view('client.chat.show', compact('conversation'));
    }

    // Fetch messages (AJAX)
    public function messages($id)
    {
        $user = Auth::guard('client')->user();
        $message = Message::findOrFail($id);

        $messages = Message::where(function ($q) use ($user) {
            $q->where('receiver_number', $user->whatsapp_meta_no)
                ->orWhere('sender_number', $user->whatsapp_meta_no);
        })
            ->where(function ($q) use ($message) {
                $q->where('receiver_number', $message->sender_number)
                    ->orWhere('sender_number', $message->sender_number);
            })
            ->get()
            ->map(function ($msg) use ($user) {
                $msg->sender_type = $msg->sender_number === $user->whatsapp_meta_no ? 'self' : 'other';
                return $msg;
            });

        return response()->json($messages);
    }

    // Send a message
    public function send(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        $client = auth('client')->user();

        // Ensure conversation belongs to this client
        $message = Message::where('id', $id)->first();

        // ✅ Use ChatSystemController logic
        $acc = WhatsAppAccount::where('is_active', true)->latest('id')->first();
        if (!$acc) {
            return response()->json(['error' => 'No active WhatsApp account'], 500);
        }

        $senderBusinessE164 = '+' . ltrim($acc->display_phone_number, '+');

        // Call WhatsApp API directly
        $resp = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $client->permanent_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post("https://graph.facebook.com/{$client->api_version}/{$client->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'   => $message->sender_number,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $request->message],
        ]);

        $json = $resp->json();
        // dd($json);

        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];

            // ✅ Always ensure/get conversation
            $conv = $this->ensureConversationForNumber($client);

            Message::create([
                'conversation_id'     => $conv->id,   // ✅ Always use valid conversation id
                'whatsapp_account_id' => $acc->id,
                'sender_id'           => $client->id,
                'client_ref'          => $waId,
                'sender_number'       => $client->whatsapp_meta_no,
                'receiver_number'     => $message->sender_number,
                'body'                => $request->message,
                'meta'                => [
                    'source'            => 'whatsapp',
                    'direction'         => 'out',
                    'type'              => 'text',
                    'phone_number_id'   => $client->phone_number_id,
                    'business_account'  => $client->business_account_id,
                    'response'          => $json,
                ],
            ]);
        } else {
            // dd('else');
            \Log::error('Failed to send WhatsApp message', [

                'code'     => $resp->status(),
                'error'    => $resp->body(),
            ]);
        }

        return response()->json($json, $resp->status());
    }

    protected function ensureConversationForNumber($client)
    {
        // dd($client);
        $conv = Conversation::where('channel', 'whatsapp')
            ->where('client_id', $client->id)
            ->first();

        if (!$conv) {
            // dd('if');
            $conv = Conversation::create([
                'title'       => 'WhatsApp: ' . ($client->name ?: substr($client->mobile ?? '', -6)),
                'channel'     => 'whatsapp',
                'customer_id' => $client->id,
                'client_id' => $client->id,
                'meta'        => ['receiver' => $client->whatsapp_meta_no],
            ]);
        } else {
            // dd('else');
            $meta = $conv->meta ?? [];
            if ($conv->whatsapp_meta_no && ($meta['receiver'] ?? null) !== $conv->whatsapp_meta_no) {
                $meta['receiver'] = $conv->whatsapp_meta_no;
                $conv->meta = $meta;
                $conv->save();
            }
        }
        // dd($conv);
        return $conv;
    }
}
