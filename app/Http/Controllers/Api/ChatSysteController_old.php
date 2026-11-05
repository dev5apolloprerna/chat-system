<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Models\MessageReceipt;



class ChatSystemController extends Controller
{
    private function apiUrl(string $path): string
    {
        $v = env('WHATSAPP_API_VERSION', 'v21.0');
        $pnid = env('WHATSAPP_PHONE_NUMBER_ID');
        return "https://graph.facebook.com/{$v}/{$pnid}{$path}";
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.env('WHATSAPP_ACCESS_TOKEN'),
            'Content-Type'  => 'application/json',
        ];
    }

    private function senderNumberE164(): ?string
    {
        // Optional: keep your sender for validation (digits only, no +)
        $num = env('WHATSAPP_NUMBER'); // e.g., 916356763136
        return $num ? preg_replace('/\D+/', '', $num) : null;
    }

    /** ========== Verify webhook (GET) ========== */
     public function verify(Request $request)
    {
        // Meta sends: hub.mode, hub.verify_token, hub.challenge
        // In PHP, dots can be converted to underscores. Handle both:
        $mode        = $request->query('hub_mode', $request->query('hub.mode'));
        $verifyToken = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge   = $request->query('hub_challenge', $request->query('hub.challenge'));

       // if ($mode === 'subscribe' && $verifyToken === config('services.whatsapp.verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        //}
        return response('Invalid verify token', 403);

    }



    /** ========== Receive webhook (POST) ========== */
  public function receive(Request $r)
{
    // Log the incoming payload for debugging (optional)
   // Log::info('Received message from WhatsApp:', $r->all());

    // Check if the payload contains messages
    if (isset($r->all()['entry'][0]['changes'][0]['value']['messages'])) {
        $messages = $r->all()['entry'][0]['changes'][0]['value']['messages'];

        foreach ($messages as $m) {
            try {
                // Extract message data
                $from = preg_replace('/\D+/', '', $m['from']);  // Phone number (E.164 format)
                $waMsgId = $m['id'] ?? Str::uuid()->toString();  // WhatsApp message ID
                $type = $m['type'] ?? 'text';
                $body = $type === 'text' ? ($m['text']['body'] ?? '') : '[' . $type . ']';

                // Ensure the user exists in the users table
                $user = User::find(1);  // Example: You may want to dynamically fetch user based on `from` or another value
                if (!$user) {
                    // Handle the case where the user doesn't exist
                    Log::error("User with ID 1 does not exist");
                    return response()->json(['error' => 'User not found'], 400);
                }

                // Ensure conversation exists for the sender's phone number
                $conv = $this->ensureConversationForNumber($from);

                // Store the incoming message and add the user to the conversation
                DB::transaction(function () use ($conv, $waMsgId, $body, $user) {
                    // Create the message record
                    $message = Message::create([
                        'conversation_id' => $conv->id,
                        'sender_id' => $user->id,  // Use the valid user ID
                        'client_ref' => $waMsgId,  // WhatsApp message ID
                        'body' => $body,  // Message content
                        'meta' => json_encode(['source' => 'whatsapp']), // Ensure meta is JSON encoded
                    ]);

                    // Add the user to the conversation participants
                    ConversationParticipant::create([
                        'conversation_id' => $conv->id,
                        'user_id' => $user->id,  // Use the valid user ID
                        'role' => 'admin',       // Set the role as 'admin' or 'member'
                    ]);

                    // Update the conversation timestamp
                    $conv->touch();
                });

            } catch (\Throwable $e) {
                Log::error('Error processing incoming message:', ['ex' => $e->getMessage()]);
            }
        }
    }

    return response()->noContent();
}


    /** ========== Send TEXT (stores outbound) ========== */
    public function sendText(Request $r)
    {
        $data = $r->validate([
            'to'   => ['required','string'],
            'text' => ['required','string','max:4096'],
        ]);

        $to = preg_replace('/\D+/', '', $data['to']);
        if ($this->senderNumberE164() && $to === $this->senderNumberE164()) {
            return response()->json(['message'=>'Recipient cannot be the sender number. Use a different WhatsApp number.'], 422);
        }

        // 1) Send to Meta
        $resp = Http::withHeaders($this->headers())
            ->post($this->apiUrl('/messages'), [
                'messaging_product' => 'whatsapp',
                'to'   => $to,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $data['text']],
            ]);

        $json = $resp->json();

        // 2) Persist locally if API accepted
        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
                $waId = $json['messages'][0]['id'];
                $conv = $this->ensureConversationForNumber($to);

                DB::transaction(function () use ($conv, $waId, $data) {
                    $msg = Message::create([
                        'id'              => Str::ulid()->toBase32(),
                        'conversation_id' => $conv->id,
                        'sender_id'       => auth()->id() ?? $this->agentUserId(),
                        'client_ref'      => $waId,
                        'body'            => $data['text'],
                        'meta'            => json_encode(['target' => 'whatsapp', 'type' => 'sent']),
                    ]);

                    if (User::find($this->customerUserId())) {
                        MessageReceipt::create([
                            'message_id' => $msg->id,
                            'user_id'    => $this->customerUserId(),
                            'status'     => 'sent',
                        ]);
                    }


                    $conv->touch();
                });
            } else {
                Log::error('Failed to send WhatsApp message', [
                    'to' => $to,
                    'error' => $resp->body(),
                ]);
            }


        return response()->json($json, $resp->status());
    }

    /** ========== Send TEMPLATE (for outside 24h) ========== */
    public function sendTemplate(Request $r)
    {
        $data = $r->validate([
            'to'         => ['required','string'],
            'name'       => ['required','string'], // approved template
            'lang'       => ['required','string'], // en_US, en_GB, etc.
            'components' => ['sometimes','array'],
        ]);

        $to = preg_replace('/\D+/', '', $data['to']);
        if ($this->senderNumberE164() && $to === $this->senderNumberE164()) {
            return response()->json(['message'=>'Recipient cannot be the sender number.'], 422);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'       => $to,
            'type'     => 'template',
            'template' => [
                'name'     => $data['name'],
                'language' => ['code' => $data['lang']],
            ],
        ];
        if (!empty($data['components'])) {
            $payload['template']['components'] = $data['components'];
        }

        $resp = Http::withHeaders($this->headers())->post($this->apiUrl('/messages'), $payload);
        return response()->json($resp->json(), $resp->status());
    }

    /* ===== Helpers: map phone to conversation & pseudo users ===== */

   private function ensureConversationForNumber(string $e164): Conversation
    {
        // Check if the conversation exists
        $conv = Conversation::where('type', 'direct')
            ->where('title', $e164)
            ->first();
    
        if (!$conv) {
            // Create a new conversation if it doesn't exist
            $conv = Conversation::create([
                'type' => 'direct',
                'title' => $e164,
                'created_by' => $this->agentUserId(),
            ]);
    
            // Add participants to the conversation (admin and customer)
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $this->agentUserId(),
                'role' => 'admin',
            ]);
    
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $this->customerUserId(),
                'role' => 'member',
            ]);
        }
    
        return $conv;
    }


    private function agentUserId(): int { return 1; }     // your internal agent/system user id
    private function customerUserId(): int { return 2; }  // placeholder; replace with real customer-mapping


public function listConversations(Request $r)
{
    $convos = Conversation::query()
        ->withCount('messages')
        ->orderByDesc('updated_at')
        ->paginate($r->integer('per_page', 20));

    return response()->json($convos);
}

public function showConversation(Request $r, $conversationId)
{
    $conv = Conversation::with(['participants','messages' => function($q){
        $q->latest()->limit(30);
    }])->findOrFail($conversationId);

    return response()->json($conv);
}

public function listMessages(Request $r, $conversationId)
{
    $after = $r->query('after'); // ULID for seek pagination
    $limit = min(100, max(1, (int)$r->query('limit', 30)));

    $q = Message::where('conversation_id', $conversationId)->orderBy('created_at','desc');
    if ($after) $q->where('id','<',$after);

    $messages = $q->limit($limit)->get()->reverse()->values();
    return response()->json([
        'data' => $messages,
        'next_after' => $messages->count() ? $messages->first()->id : null,
    ]);
}



}


