<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
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
            // ->selectRaw('
            //     MAX(id) as id,
            //     sender_number,
            //     sender_name,
            //     COUNT(CASE WHEN is_read = false THEN 1 END) as unread_count
            // ')
            ->selectRaw('MAX(id) as id, sender_number, sender_name, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->where('receiver_number', $user->whatsapp_meta_no)
            ->groupBy('sender_number', 'sender_name')
            ->orderByDesc(DB::raw('MAX(id)'))
            ->get();


        return view('client.chat.index', compact('conversations'));
    }

    // Fetch messages (AJAX)
    public function messages($id)
    {
        // dd($id);
        $user = Auth::guard('client')->user();
        // dd($user);
        $message = Message::findOrFail($id);
        // dd($message);

        // ✅ Mark all unread messages from that sender as read
        Message::where('receiver_number', $user->whatsapp_meta_no)
            ->where('sender_number', $message->sender_number)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'isMessageStatus' => 3
            ]);

        $messages = Message::where(function ($q) use ($user) {
            $q->where('receiver_number', $user->whatsapp_meta_no)
                ->orWhere('sender_number', $user->whatsapp_meta_no);
        })
            ->where(function ($q) use ($message) {
                $q->where('receiver_number', $message->sender_number)
                    ->orWhere('sender_number', $message->sender_number);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user) {
                $msg->sender_type = $msg->sender_number === $user->whatsapp_meta_no ? 'self' : 'other';
                $msg->time = $msg->created_at ? $msg->created_at->format('h:i A') : null;

                // ✅ Add formatted date for grouping
                if ($msg->created_at->isToday()) {
                    $msg->date_label = 'Today';
                } elseif ($msg->created_at->isYesterday()) {
                    $msg->date_label = 'Yesterday';
                } else {
                    $msg->date_label = $msg->created_at->format('d M Y');
                }

                return $msg;
            });
        // ->map(function ($msg) use ($user) {
        //     $msg->sender_type = $msg->sender_number === $user->whatsapp_meta_no ? 'self' : 'other';
        //     return $msg;
        // });
        // dd($messages);

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
                'is_read'             => false,
                'isMessageStatus'     => 1,
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


    // ✅ Mark message as delivered (double tick)
    public function markAsDelivered($messageId)
    {
        $message = Message::find($messageId);
        if ($message && $message->isMessageStatus < 2) {
            $message->update(['isMessageStatus' => 2]); // delivered
        }

        return response()->json(['success' => true]);
    }

    // ✅ Mark message as read (blue double tick)
    public function markAsReadStatus($messageId)
    {
        $message = Message::findOrFail($messageId);

        $message->status = 'read';
        $message->is_read = true; // Keep both in sync
        $message->save();

        return response()->json(['status' => 'success', 'message_status' => $message->status]);
    }


    public function markAsRead($conversationId)
    {
        $user = Auth::guard('client')->user();

        // Mark all unread messages in the given conversation as read
        $updated = Message::where('conversation_id', $conversationId)
            ->where('receiver_number', $user->whatsapp_meta_no)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'isMessageStatus' => 3 // read
            ]);

        return response()->json(['status' => 'success', 'updated' => $updated]);
    }
}
