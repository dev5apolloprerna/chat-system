<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\WhatsAppAccount;


class WhatsAppWebhookController extends Controller
{
    private $client;
    private $assistantId = 'asst_IAm6tSGd0qKJouJx2FYNy9FR';
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ]
        ]);
    }
    // === GET /wa/webhook (verification) ===
    public function verify(Request $request)
    {
        $mode        = $request->query('hub_mode', $request->query('hub.mode'));
        $verifyToken = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge   = $request->query('hub_challenge', $request->query('hub.challenge'));

        // Accept if the token matches *any* active WhatsApp account's verify_token
        $tokenOk = WhatsAppAccount::where('verify_token', $verifyToken)
            ->where('is_active', true)
            ->exists();
        if ($mode === 'subscribe' && $verifyToken && $tokenOk && $challenge !== null) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }
        return response('Invalid verify token', 403);
    }

    // === POST /wa/webhook (events) ===
    public function receive(Request $r)
    {
        //  dd($r);
        $secret = config('services.whatsapp.app_secret');
        if ($secret && !hash_equals('sha256=' . hash_hmac('sha256', $r->getContent(), $secret), (string) $r->header('X-Hub-Signature-256'))) {
            return response('Invalid webhook signature', 403);
        }
        $payload = $r->json()->all();
        $body = "";
        try {
            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // Resolve WhatsApp account using phone_number_id from metadata
                    // $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    // $acc = $phoneNumberId ? WhatsAppAccount::byPhoneNumberId($phoneNumberId) : null;
                    // dd($acc);

                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    $acc = $phoneNumberId ? WhatsAppAccount::byPhoneNumberId($phoneNumberId) : null;
                    if (!$acc) {
                        Log::warning('WhatsApp webhook account is not configured', ['phone_number_id' => $phoneNumberId]);
                        return response('WhatsApp account is not configured', 422);
                    }

                    // Receiver (your business number)
                    $bizDisplay = $value['metadata']['display_phone_number'] ?? $acc?->display_phone_number;
                    $receiver = Message::normalizeNumber($acc->display_phone_number);

                    // Inbound messages (customer replies)
                    foreach (($value['messages'] ?? []) as $m) {
                        $newMessage = $this->storeInbound($acc, $receiver, $m, $value['contacts'] ?? []);
                        if (!$newMessage) continue; // webhook retry already persisted
                        $conv = $newMessage->conversation;
                        $sender = $newMessage->sender_number;
                        $body = $newMessage->body;
                        try {
                            event(new MessageSent($newMessage));
                        } catch (\Throwable $e) {
                            Log::warning('Inbound message stored but broadcast failed', ['message_id' => $newMessage->id]);
                        }
                        // Auto-reply only for a newly stored inbound message, never a status event or retry.
                        $client = DB::table('clients')->where('phone_number_id', $acc->phone_number_id)->first();
                        if ($client && ($client->Assistan_flag ?? 0) == 1 && !empty($client->AssistantId)) {
                            try {
                                $reply = $this->ExecuteAIChatAPI($body, $client->AssistantId);
                                $this->send($reply, $receiver, $sender, $client, $conv);
                            } catch (\Throwable $e) {
                                Log::error('WhatsApp auto-reply failed', ['message_id' => $newMessage->id]);
                            }
                        }
                    }
                    $this->updateStatuses($value, $acc);

                }
            }

            return response('EVENT_RECEIVED', 200);
        } catch (\Throwable $e) {
            Log::error('WA receive failed', ['e' => $e->getMessage()]);
            // Let Meta retry transient storage failures instead of losing the reply.
            return response('Webhook processing failed', 500);
        }
    }
    protected function storeInbound(WhatsAppAccount $account, string $receiver, array $data, array $contacts): ?Message
    {
        if (empty($data['id']) || !Message::normalizeNumber($data['from'] ?? null)) {
            throw new \InvalidArgumentException('Inbound message requires an ID and sender');
        }
        return DB::transaction(function () use ($account, $receiver, $data, $contacts) {
            // Serialize writes per business account so concurrent retries cannot
            // create duplicate messages or split the same pair into two threads.
            WhatsAppAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if (Message::where('whatsapp_account_id', $account->id)->where('client_ref', $data['id'])->exists()) return null;
            $sender = Message::normalizeNumber($data['from']);
            $profile = collect($contacts)->first(function ($contact) use ($sender) {
                return Message::normalizeNumber($contact['wa_id'] ?? null) === $sender;
            }) ?? ($contacts[0] ?? []);
            $name = $profile['profile']['name'] ?? null;
            $customer = User::ensureForWhatsApp($sender);
            $conversation = $this->ensureConversationForNumber($customer, $receiver);
            ConversationParticipant::firstOrCreate([
                'conversation_id' => $conversation->id, 'user_id' => $customer->id,
            ], ['role' => 'member']);
            [$type, $body] = $this->extractBody($data);
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'whatsapp_account_id' => $account->id,
                'sender_id' => $customer->id,
                'client_ref' => $data['id'],
                'sender_number' => $sender,
                'sender_name' => $name,
                'receiver_number' => $receiver,
                'body' => $body,
                'is_read' => false,
                'isMessageStatus' => 1,
                'meta' => ['source' => 'whatsapp', 'direction' => 'in', 'type' => $type,
                    'phone_number_id' => $account->phone_number_id, 'raw' => $data],
            ]);
            $conversation->touch();
            return $message;
        });
    }

    private function send($Message,$Sender,$Receiver,$client, Conversation $conv)
    {
        //dd($Receiver,"call");   
      // $Sender  company whatsapp number
        
        //$client = auth('client')->user();

        // Ensure conversation belongs to this client
        //$message = Message::where('id', $id)->first();

        // âœ… Use ChatSystemController logic
        $acc = WhatsAppAccount::byPhoneNumberId((string) $client->phone_number_id);
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
            'to'   => $Receiver,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $Message],
        ]);
 
        $json = $resp->json();
        // dd($json);

        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];

            // âœ… Always ensure/get conversation
            // Reuse the inbound conversation for the reply.
            
            $newMessage = Message::create([
                'conversation_id'     => $conv->id,   // âœ… Always use valid conversation id
                'whatsapp_account_id' => $acc->id,
                'sender_id'           => null,
                'client_ref'          => $waId,
                'sender_number'       => $Sender,
                'receiver_number'     => $Receiver,
                'body'                => $Message,
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
            
            // âœ… Fire event for real-time update
            event(new MessageSent($newMessage));
        } else {
            // dd('else');
            \Log::error('Failed to send WhatsApp message', [

                'code'     => $resp->status(),
                'error'    => $resp->body(),
            ]);
        }
       
        return response()->json($json, $resp->status());
    }
    private function ExecuteAIChatAPI($body,$assistancId)
    {
        $userMessage = $body;
      
            // Step 1: Create a thread
            $threadResponse = $this->client->post('threads', [
                'json' => []
            ]);
            
            $threadData = json_decode($threadResponse->getBody(), true);
            $threadId = $threadData['id'];
          
            // Step 2: Add user message to thread
            
            $this->client->post("threads/{$threadId}/messages", [
                'json' => [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ]);

            // Step 3: Run the assistant on this thread
            $runResponse = $this->client->post("threads/{$threadId}/runs", [
                'json' => [
                    'assistant_id' => $assistancId,
                    'model' => 'gpt-4.1',
                ]
            ]);
 
            $runData = json_decode($runResponse->getBody(), true);
            $runId = $runData['id'];
           
            // Step 4: Wait for completion
            do {
                sleep(3); // give more time for assistant to reply
                $checkRun = $this->client->get("threads/{$threadId}/runs/{$runId}");
                $runStatus = json_decode($checkRun->getBody(), true);
                $status = $runStatus['status'];
            } while (in_array($status, ['queued', 'in_progress']));
 
            // Step 5: Get the final assistant message
            $messagesResponse = $this->client->get("threads/{$threadId}/messages");
            $messages = json_decode($messagesResponse->getBody(), true);
            $assistantReply = collect($messages['data'])
                ->where('role', 'assistant')
                ->sortByDesc('created_at')
                ->first()['content'][0]['text']['value'] ?? 'No reply found.';
           
            return $assistantReply;
    }
    /** Extract readable body/title from WA message */
    protected function extractBody(array $m): array
    {
        $type = $m['type'] ?? 'text';
        $body = '';

        if ($type === 'text') {
            $body = $m['text']['body'] ?? '';
        } elseif ($type === 'interactive') {
            $iType = $m['interactive']['type'] ?? null;
            if ($iType === 'button_reply') {
                $body = $m['interactive']['button_reply']['title'] ?? '';
            } elseif ($iType === 'list_reply') {
                $body = $m['interactive']['list_reply']['title'] ?? '';
            } else {
                $body = '[interactive]';
            }
        } elseif (in_array($type, ['image', 'video', 'audio', 'document', 'sticker', 'location', 'contacts', 'reaction'])) {
            $body = $m[$type]['caption'] ?? '[' . $type . ']';
        } else {
            $body = '[' . $type . ']';
        }

        return [$type, $body];
    }

    /** Create/find a conversation per WA customer */
    protected function ensureConversationForNumber(User $customer, ?string $receiver): Conversation
    {
        return Conversation::forWhatsAppPair($customer, $receiver ?? '');
    }

    /** Fallback if you don’t have User::ensureForWhatsApp() */
    protected function fallbackEnsureForWhatsApp(string $phone, ?string $name = null): User
    {
        $phone = '+' . ltrim($phone, '+');
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            $user = User::create([
                'name'     => $name ?: 'WhatsApp ' . substr($phone, -4),
                'email'    => 'wa_' . preg_replace('/\D/', '', $phone) . '@example.invalid',
                'phone'    => $phone,
                'password' => bcrypt(Str::random(32)),
            ]);
        }
        return $user;
    }

    // Meta sends delivery/read updates to the same callback as inbound messages.
    public function handleStatus(Request $request)
    {
        return $this->receive($request);
    }

    protected function updateStatuses(array $value, WhatsAppAccount $account): void
    {
        foreach ($value['statuses'] ?? [] as $status) {
            $rank = ['sent' => 1, 'delivered' => 2, 'read' => 3][$status['status'] ?? ''] ?? null;
            if (!$rank || empty($status['id'])) continue;
            $message = Message::where('whatsapp_account_id', $account->id)
                ->where('client_ref', $status['id'])->first();
            if (!$message || $message->isMessageStatus >= $rank) continue;
            $message->isMessageStatus = $rank;
            if ($rank === 3) $message->is_read = true;
            $message->save();
            if ($rank === 3) {
                try {
                    event(new MessageRead($message->conversation_id, [$message->id]));
                } catch (\Throwable $e) {
                    Log::warning('Read status stored but broadcast failed', ['message_id' => $message->id]);
                }
            }
        }
    }
}
