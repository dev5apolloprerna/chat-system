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
use App\Events\MessageSent;
use Pusher\Pusher;

class ChatController extends Controller
{
    // Chat dashboard (all conversations for logged in client)
    public function index()
    {
        $user = Auth::guard('client')->user();

        $number = Message::normalizeNumber($user->whatsapp_meta_no);
        // Keep a stable message anchor per peer, in either direction.
        $conversations = Message::where(function ($q) use ($number) {
            $q->whereIn('sender_number', Message::numberVariants($number))->orWhereIn('receiver_number', Message::numberVariants($number));
        })->orderByDesc('id')->get()->groupBy(function ($message) use ($number) {
            return $message->sender_number === $number ? $message->receiver_number : $message->sender_number;
        })->map(function ($messages, $peer) use ($number) {
            $anchor = $messages->first();
            return (object) [
                'id' => $anchor->id,
                'conversation_id' => $anchor->conversation_id,
                'sender_number' => $peer,
                'sender_name' => optional($messages->firstWhere('sender_number', $peer))->sender_name,
                'unread_count' => $messages->whereIn('receiver_number', Message::numberVariants($number))->where('is_read', false)->count(),
            ];
        })->values();
        if (request()->expectsJson()) return response()->json($conversations);

        return view('client.chat.index', compact('conversations'));
    }

    // Fetch messages (AJAX)
    public function messages($id)
    {
        // dd($id);
        $user = Auth::guard('client')->user();
        // dd($user);
        $message = $this->ownedMessage($id, $user);
        $number = Message::normalizeNumber($user->whatsapp_meta_no);
        $peer = $message->sender_number === $number ? $message->receiver_number : $message->sender_number;
        // ✅ Mark all unread messages from that sender as read
        Message::whereIn('receiver_number', Message::numberVariants($user->whatsapp_meta_no))
            ->whereIn('sender_number', Message::numberVariants($peer))
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'isMessageStatus' => 3
            ]);

        $messageIds = Message::whereIn('receiver_number', Message::numberVariants($user->whatsapp_meta_no))
            ->whereIn('sender_number', Message::numberVariants($peer))
            ->where('is_read', true)
            ->pluck('id');

        // 🔥 Broadcast to update blue ticks in sender’s chat
        // Read state is persisted even when the optional broadcaster is unavailable.

        $messages = Message::where(function ($q) use ($user) {
            $q->whereIn('receiver_number', Message::numberVariants($user->whatsapp_meta_no))
                ->orWhereIn('sender_number', Message::numberVariants($user->whatsapp_meta_no));
        })
            ->where(function ($q) use ($peer) {
                $q->whereIn('receiver_number', Message::numberVariants($peer))
                    ->orWhereIn('sender_number', Message::numberVariants($peer));
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user) {
                $msg->sender_type = $msg->sender_number === Message::normalizeNumber($user->whatsapp_meta_no) ? 'self' : 'other';
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
        //     $msg->sender_type = $msg->sender_number === Message::normalizeNumber($user->whatsapp_meta_no) ? 'self' : 'other';
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

        $message = $this->ownedMessage($id, $client);
        $sender = Message::normalizeNumber($client->whatsapp_meta_no);
        $recipient = $message->sender_number === $sender ? $message->receiver_number : $message->sender_number;
        $acc = WhatsAppAccount::byPhoneNumberId((string) $client->phone_number_id);
        if (!$acc || Message::normalizeNumber($acc->display_phone_number) !== $sender) {
            return response()->json(['error' => 'WhatsApp account does not match this client'], 422);
        }

        $conv = $message->conversation;
        abort_unless($conv, 422, 'Message conversation is missing');

        // Call WhatsApp API directly
        $resp = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $client->permanent_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post("https://graph.facebook.com/{$client->api_version}/{$client->phone_number_id}/messages", [
            'messaging_product' => 'whatsapp',
            'to'   => ltrim($recipient, '+'),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $request->message],
        ]);

        $json = $resp->json();
        // dd($json);

        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];

            // ✅ Always ensure/get conversation


            $newMessage = Message::create([
                'conversation_id'     => $conv->id,   // ✅ Always use valid conversation id
                'whatsapp_account_id' => $acc->id,
                'sender_id'           => auth('web')->id(),
                'client_ref'          => $waId,
                'sender_number'       => $sender,
                'receiver_number'     => $recipient,
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

            // ✅ Fire event for real-time update
            try {
                event(new MessageSent($newMessage));
            } catch (\Throwable $e) {
                Log::warning('Message stored but broadcast failed', ['message_id' => $newMessage->id]);
            }
            return response()->json($newMessage, 201);
        } else {
            // dd('else');
            \Log::error('Failed to send WhatsApp message', [

                'code'     => $resp->status(),
                'error'    => $resp->body(),
            ]);
        }

        return response()->json(['error' => $json['error']['message'] ?? 'WhatsApp did not accept the message'], $resp->successful() ? 502 : $resp->status());
    }

    protected function ownedMessage($id, $client): Message
    {
        $number = Message::normalizeNumber($client->whatsapp_meta_no);
        abort_if($number === '', 422, 'Client WhatsApp number is not configured');
        $message = Message::where('id', $id)->where(function ($q) use ($number) {
            $q->whereIn('sender_number', Message::numberVariants($number))->orWhereIn('receiver_number', Message::numberVariants($number));
        })->first();
        abort_unless($message, 404, 'Message not found');
        return $message;
    }

    // ✅ Mark message as delivered (double tick)
    public function markAsDelivered($messageId)
    {
        $message = $this->ownedMessage($messageId, auth('client')->user());
        if ($message && $message->isMessageStatus < 2) {
            $message->update(['isMessageStatus' => 2]); // delivered
        }

        return response()->json(['success' => true]);
    }

    // ✅ Mark message as read (blue double tick)
    public function markAsReadStatus($messageId)
    {
        $message = $this->ownedMessage($messageId, auth('client')->user());

        $message->isMessageStatus = 3;
        $message->is_read = true; // Keep both in sync
        $message->save();

        return response()->json(['status' => 'success', 'message_status' => $message->isMessageStatus]);
    }


    public function markAsRead($conversationId)
    {
        $user = Auth::guard('client')->user();

        // Fetch all unread messages first
        $messageIds = Message::where('conversation_id', $conversationId)
            ->whereIn('receiver_number', Message::numberVariants($user->whatsapp_meta_no))
            ->where('is_read', false)
            ->pluck('id');

        // Update message statuses
        $updated = Message::whereIn('id', $messageIds)
            ->update([
                'is_read' => true,
                'isMessageStatus' => 3 // read
            ]);

        // 🔥 Fire broadcast event if any message updated
        if ($updated > 0) {
            broadcast(new \App\Events\MessageRead($conversationId, $messageIds->toArray()))->toOthers();
        }

        // // Mark all unread messages in the given conversation as read
        // $updated = Message::where('conversation_id', $conversationId)
        //     ->whereIn('receiver_number', Message::numberVariants($user->whatsapp_meta_no))
        //     ->where('is_read', false)
        //     ->update([
        //         'is_read' => true,
        //         'isMessageStatus' => 3 // read
        //     ]);

        return response()->json(['status' => 'success', 'updated' => $updated]);
    }
}
