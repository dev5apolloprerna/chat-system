<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
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
        //if ($mode === 'subscribe' && $tokenOk && $challenge !== null) {
        return response($challenge, 200)->header('Content-Type', 'text/plain');
        //}
        return response('Invalid verify token', 403);
    }

    // === POST /wa/webhook (events) ===
    public function receive(Request $r)
    {
        //  dd($r);
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

                    $phoneNumberId = $value['metadata']['phone_number_id']
                        ?? WhatsAppAccount::where('is_active', true)->value('phone_number_id');
                    // dd($phoneNumberId)    ;

                    $acc = $phoneNumberId
                        ? WhatsAppAccount::byPhoneNumberId($phoneNumberId)
                        : null;
                    // dd($acc)    ;

                    // Receiver (your business number)
                    $bizDisplay = $value['metadata']['display_phone_number'] ?? $acc?->display_phone_number;
                    $receiver   = $bizDisplay ? ('+' . ltrim($bizDisplay, '+')) : null;

                    // Inbound messages (customer replies)
                    foreach (($value['messages'] ?? []) as $m) {
                        $waMsgId = $m['id'] ?? Str::uuid()->toString();
                        // Idempotent insert (unique by client_ref + whatsapp_account_id)
                        $exists = Message::where('client_ref', $waMsgId)
                            ->when($acc, fn($q) => $q->where('whatsapp_account_id', $acc->id))
                            ->exists();
                        // dd($exists);
                        if ($exists) continue;

                        // Sender (customer)
                        // dd($exists);
                        $fromDigits = preg_replace('/\D+/', '', ($m['from'] ?? ''));
                        if (!$fromDigits) {
                            Log::warning('WA missing sender', ['m' => $m]);
                            continue;
                        }
                        $sender = '+' . $fromDigits;

                        // Extract a readable body and type
                        [$type, $body] = $this->extractBody($m);

                        // Ensure a "customer" user for this WA number (or use your own mapper)
                        $profileName = $value['contacts'][0]['profile']['name'] ?? null;
                        // dd($profileName);
                        $customer = method_exists(User::class, 'ensureForWhatsApp')
                            ? User::ensureForWhatsApp($sender, $profileName)
                            : $this->fallbackEnsureForWhatsApp($sender, $profileName);
                        // dd($customer)    ;

                        // Pick an agent if you route (optional)
                        $agent = User::find(1); // adjust to your rules; can be null

                        // Ensure/load conversation for this customer
                        $conv = $this->ensureConversationForNumber($customer, $receiver);

                        DB::transaction(function () use ($conv, $acc, $waMsgId, $body, $profileName, $customer, $agent, $m, $sender, $receiver, $type) {

                            // Participants (no duplicates)
                            ConversationParticipant::firstOrCreate([
                                'conversation_id' => $conv->id,
                                'user_id'         => $customer->id,
                            ], ['role' => 'member']);

                            if ($agent) {
                                ConversationParticipant::firstOrCreate([
                                    'conversation_id' => $conv->id,
                                    'user_id'         => $agent->id,
                                ], ['role' => 'admin']);
                            }

                            // Save message row
                            Message::create([
                                'conversation_id'     => $conv->id,
                                'whatsapp_account_id' => $acc?->id,
                                'sender_id'           => $customer->id,
                                'client_ref'          => $waMsgId,     // wamid…
                                'sender_number'       => $sender,      // +919…
                                'sender_name'         => $profileName,
                                'receiver_number'     => $receiver,    // +9163567…
                                'body'                => $body,
                                'is_read'             => false,
                                'isMessageStatus'     => 1,
                                'meta'                => [
                                    'source'            => 'whatsapp',
                                    'type'              => $type,
                                    'sender'            => $sender,
                                    'receiver'          => $receiver,
                                    'phone_number_id'   => $acc?->phone_number_id,
                                    'business_account'  => $acc?->business_account_id,
                                    'raw'               => $m,
                                ],
                            ]);

                            $conv->touch();
                        });
                    }
                    # This Code for Auto Ai chatbot Based Replay Start
                        $client = DB::table('clients')->where('whatsapp_meta_no', $receiver)->first();
                        if ($client && $client->Assistan_flag == 1) {
                            $assistancId = $client->AssistantId ?? null;
                            if (!empty($assistancId)) {
                                $airesponse = $this->ExecuteAIChatAPI($body, $assistancId);
                                $send_res = $this->send($airesponse, $receiver, $sender, $client);
                            }
                        }
                    # This Code for Auto Ai chatbot Based Replay End

                    // Optional: delivery/read statuses for your outbound messages
                    foreach (($value['statuses'] ?? []) as $st) {
                        // If you store outbound messages with client_ref = wa_message_id
                        // you can update status in meta here.
                        // Example:
                        // Message::where('client_ref', $st['id'] ?? '')
                        //     ->when($acc, fn($q) => $q->where('whatsapp_account_id', $acc->id))
                        //     ->update(['meta->last_status' => $st['status'] ?? 'unknown']);
                    }
                }
            }

            return response('EVENT_RECEIVED', 200);
        } catch (\Throwable $e) {
            Log::error('WA receive failed', ['e' => $e->getMessage()]);
            // Still 200 to avoid retry storm
            return response('EVENT_RECEIVED', 200);
        }
    }
    private function send($Message,$Sender,$Receiver,$client)
    {
        //dd($Receiver,"call");   
      // $Sender  company whatsapp number
        
        //$client = auth('client')->user();

        // Ensure conversation belongs to this client
        //$message = Message::where('id', $id)->first();

        // âœ… Use ChatSystemController logic
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
            'to'   => $Receiver,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $Message],
        ]);
 
        $json = $resp->json();
        // dd($json);

        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];

            // âœ… Always ensure/get conversation
            $conv = $this->ensureConversationForNumber_ResponceChatBoat($client);
            
            $newMessage = Message::create([
                'conversation_id'     => $conv->id,   // âœ… Always use valid conversation id
                'whatsapp_account_id' => $acc->id,
                'sender_id'           => $client->id,
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
        // One open thread per WA customer (adapt if you have a different rule)
        $conv = Conversation::where('channel', 'whatsapp')
            ->where('customer_id', $customer->id)
            ->first();

        if (!$conv) {
            $conv = Conversation::create([
                'title'       => 'WhatsApp: ' . ($customer->name ?: substr($customer->phone ?? '', -6)),
                'channel'     => 'whatsapp',
                'customer_id' => $customer->id,
                'meta'        => ['receiver' => $receiver],
            ]);
        } else {
            $meta = $conv->meta ?? [];
            if ($receiver && ($meta['receiver'] ?? null) !== $receiver) {
                $meta['receiver'] = $receiver;
                $conv->meta = $meta;
                $conv->save();
            }
        }

        return $conv;
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

    // whatsapp status webhook
    public function handleStatus(Request $request)
    {
        Log::info('Webhook received whatsapp status', $request->all());

        $data = $request->all();

        // ✅ Check nested structure
        if (
            empty($data['entry'][0]['changes'][0]['value']['statuses']) ||
            !is_array($data['entry'][0]['changes'][0]['value']['statuses'])
        ) {
            return response()->json(['status' => 'ignored']);
        }

        $statuses = $data['entry'][0]['changes'][0]['value']['statuses'];

        foreach ($statuses as $status) {
            $waMessageId = $status['id'] ?? null;
            $messageStatus = $status['status'] ?? null;

            // ✅ Only act when message is read
            if ($messageStatus === 'read' && $waMessageId) {
                $message = Message::where('client_ref', $waMessageId)->first();

                if ($message) {
                    $message->update([
                        'is_read' => true,
                        'isMessageStatus' => 3, // mark as read (blue tick)
                    ]);

                    // ✅ Optional: broadcast real-time update to frontend
                    event(new MessageRead($message->conversation_id, [$message->id]));
                } else {
                    Log::warning("No message found for WhatsApp ID: {$waMessageId}");
                }
            }
        }

        return response()->json(['success' => true]);
    }
    
    
    protected function ensureConversationForNumber_ResponceChatBoat($client)
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
