<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\WhatsAppAccount;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class WhatsAppWebhookController extends Controller
{

    public function index(Request $request)
    {
        $messages = Message::with('sender')->latest()->take(20)->get()->reverse();

        return view('whatsapp.chat', compact('messages'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $message = Message::create([
            'user_id' => Auth::id() ?? 1, // fallback user
            'body'    => $request->body,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
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
        $payload = $r->json()->all();

        try {
            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // Resolve WhatsApp account using phone_number_id from metadata
                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    $acc = $phoneNumberId ? WhatsAppAccount::byPhoneNumberId($phoneNumberId) : null;

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
                        if ($exists) continue;

                        // Sender (customer)
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
                        $customer = method_exists(User::class, 'ensureForWhatsApp')
                            ? User::ensureForWhatsApp($sender, $profileName)
                            : $this->fallbackEnsureForWhatsApp($sender, $profileName);

                        // Pick an agent if you route (optional)
                        $agent = User::find(1); // adjust to your rules; can be null

                        // Ensure/load conversation for this customer
                        $conv = $this->ensureConversationForNumber($customer, $receiver);

                        DB::transaction(function () use ($conv, $acc, $waMsgId, $body, $customer, $agent, $m, $sender, $receiver, $type) {

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
                                'receiver_number'     => $receiver,    // +9163567…
                                'body'                => $body,
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
}
