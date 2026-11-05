<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\WhatsAppAccount;

class ChatSystemController extends Controller
{
    /* ========================
     * SEND OUTBOUND TEXT
     * ======================== */
    public function sendText(Request $r)
    {
        $data = $r->validate([
            'to'   => ['required','string'],
            'text' => ['required','string','max:4096'],
            
        ]);

        $toDigits = preg_replace('/\D+/', '', $data['to']);
        $toE164   = '+' . $toDigits;

        $acc = $this->currentAccount();
        if (!$acc) return response()->json(['message' => 'No active WhatsApp account'], 500);

        $senderBusinessE164 = $this->senderNumberE164();
        if ($senderBusinessE164 && $toE164 === $senderBusinessE164) {
            return response()->json(['message'=>'Recipient cannot be the sender number'], 422);
        }

        $resp = Http::withHeaders($this->headers())
            ->post($this->apiUrl('/messages'), [
                'messaging_product' => 'whatsapp',
                'to'   => ltrim($toE164, '+'),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $data['text']],
            ]);
        // dd($resp)    ;

        $json = $resp->json();

        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];

            $customer = $this->ensureCustomerUser($toE164);
            $conv = $this->ensureConversationForNumber($customer, $senderBusinessE164);

            DB::transaction(function () use ($conv, $acc, $waId, $data, $customer, $senderBusinessE164, $toE164) {
                ConversationParticipant::firstOrCreate(
                    ['conversation_id' => $conv->id, 'user_id' => $customer->id],
                    ['role' => 'member']
                );
                if ($this->agentUserId()) {
                    ConversationParticipant::firstOrCreate(
                        ['conversation_id' => $conv->id, 'user_id' => $this->agentUserId()],
                        ['role' => 'admin']
                    );
                }

                $message = Message::create([
                    'conversation_id'     => $conv->id,
                    'whatsapp_account_id' => $acc->id,
                    'sender_id'           => $this->agentUserId(),
                    'client_ref'          => $waId,
                    'sender_number'       => $senderBusinessE164,
                    'receiver_number'     => $toE164,
                    'body'                => $data['text'],
                    'meta'                => [
                        'source'            => 'whatsapp',
                        'direction'         => 'out',
                        'type'              => 'text',
                        'phone_number_id'   => $acc->phone_number_id,
                        'business_account'  => $acc->business_account_id,
                    ],
                ]);

                $conv->touch();
                DB::afterCommit(function () use ($message) {
                    try { event(new \App\Events\MessageSent($message)); }
                    catch (\Throwable $e) { Log::warning('Outbound message stored but broadcast failed', ['message_id' => $message->id]); }
                });
            });
        } else {
            Log::error('Failed to send WhatsApp message', [
                'to'    => $toE164,
                'code'  => $resp->status(),
                'error' => $resp->body(),
            ]);
        }

        return response()->json($json, $resp->status());
    }

    /* ========================
     * Template
     * ======================== */

    public function sendTemplate(Request $r)
    {
        $data = $r->validate([
            'to' => ['required','string'],
            'template.name' => ['required','string'],
            'template.language' => ['required','string'], // e.g. en_US
            'template.components' => ['nullable','array'],
        ]);
    
        $toDigits = preg_replace('/\D+/', '', $data['to']);
        $toE164   = '+' . $toDigits;
    
        $acc = $this->currentAccount();
        if (!$acc) {
            return response()->json(['message' => 'No active WhatsApp account'], 500);
        }
    
        $senderBusinessE164 = $this->senderNumberE164();
        if ($senderBusinessE164 && $toE164 === $senderBusinessE164) {
            return response()->json(['message'=>'Recipient cannot be the sender number'], 422);
        }
    
        // Build WhatsApp payload
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'       => ltrim($toE164, '+'),
            'type'     => 'template',
            'template' => [
                'name'      => $data['template']['name'],
                'language'  => [ 'code' => $data['template']['language'] ],
            ],
        ];
    
        if (!empty($data['template']['components'])) {
            $payload['template']['components'] = $data['template']['components'];
        }
    
        // Send to Meta
        $resp = Http::withHeaders($this->headers())
            ->post($this->apiUrl('/messages'), $payload);
    
        $json = $resp->json();
    
        // Save message to DB if API succeeded
        if ($resp->successful() && !empty($json['messages'][0]['id'])) {
            $waId = $json['messages'][0]['id'];
            $customer = $this->ensureCustomerUser($toE164);
            $conv = $this->ensureConversationForNumber($customer, $senderBusinessE164);
    
            DB::transaction(function () use ($conv, $acc, $waId, $data, $customer, $senderBusinessE164, $toE164) {
                ConversationParticipant::firstOrCreate(
                    ['conversation_id' => $conv->id, 'user_id' => $customer->id],
                    ['role' => 'member']
                );
                if ($this->agentUserId()) {
                    ConversationParticipant::firstOrCreate(
                        ['conversation_id' => $conv->id, 'user_id' => $this->agentUserId()],
                        ['role' => 'admin']
                    );
                }
    
                $message = Message::create([
                    'conversation_id'     => $conv->id,
                    'whatsapp_account_id' => $acc->id,
                    'sender_id'           => $this->agentUserId(),
                    'client_ref'          => $waId,
                    'sender_number'       => $senderBusinessE164,
                    'receiver_number'     => $toE164,
                    'body'                => 'TEMPLATE: '.$data['template']['name'],
                    'meta'                => [
                        'source'            => 'whatsapp',
                        'direction'         => 'out',
                        'type'              => 'template',
                        'template'          => $data['template'],
                        'phone_number_id'   => $acc->phone_number_id,
                        'business_account'  => $acc->business_account_id,
                    ],
                ]);
    
                $conv->touch();
                DB::afterCommit(function () use ($message) {
                    try { event(new \App\Events\MessageSent($message)); }
                    catch (\Throwable $e) { Log::warning('Outbound message stored but broadcast failed', ['message_id' => $message->id]); }
                });
            });
        } else {
            Log::error('Failed to send WhatsApp template', [
                'to'    => $toE164,
                'code'  => $resp->status(),
                'error' => $resp->body(),
            ]);
        }
    
        return response()->json($json, $resp->status());
    }

    /* ========================
     * HELPERS
     * ======================== */
    protected function currentAccount(): ?WhatsAppAccount
    {
        $query = WhatsAppAccount::where('is_active', true);
        $phoneNumberId = auth('client')->user()?->phone_number_id ?? request('phone_number_id');
        if ($phoneNumberId) return $query->where('phone_number_id', $phoneNumberId)->first();
        // A default is safe only when there is exactly one configured account.
        return $query->count() === 1 ? $query->first() : null;
    }

    protected function apiUrl(string $path): string
    {
        $acc = $this->currentAccount();
        $base = "https://graph.facebook.com/{$acc->api_version}/{$acc->phone_number_id}";
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    protected function headers(): array
    {
        $acc = $this->currentAccount();
        // dd($acc);
        return [
            'Authorization' => 'Bearer ' . ($acc->access_token ?? $acc->access_token_encrypted),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    protected function senderNumberE164(): ?string
    {
        $acc = $this->currentAccount();
        return $acc && $acc->display_phone_number
            ? ('+'.ltrim($acc->display_phone_number, '+'))
            : null;
    }

    protected function agentUserId(): ?int
    {
        return auth()->id();
    }

    protected function ensureCustomerUser(string $e164, ?string $name = null): User
    {
        $e164 = '+' . ltrim($e164, '+');
        $u = User::where('phone', $e164)->first();
        if (!$u) {
            $u = User::create([
                'name'     => $name ?: 'WhatsApp ' . substr($e164, -4),
                'email'    => 'wa_' . preg_replace('/\D/','',$e164) . '@example.invalid',
                'phone'    => $e164,
                'password' => bcrypt(Str::random(32)),
            ]);
        }
        return $u;
    }

    protected function ensureConversationForNumber(User $customer, ?string $receiverE164): Conversation
    {
        return Conversation::forWhatsAppPair($customer, $receiverE164 ?? '');
    }

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
        } elseif (in_array($type, ['image','video','audio','document','sticker','location','contacts','reaction'])) {
            $body = $m[$type]['caption'] ?? '['.$type.']';
        } else {
            $body = '['.$type.']';
        }
        return [$type, $body];
    }
}
