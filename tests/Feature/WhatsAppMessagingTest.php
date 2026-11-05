<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppMessagingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        // The checkout omits the production base chat migrations. This fixture
        // declares the columns used by these controllers; it does not alter production.
        foreach (['roles', 'customers', 'users', 'clients', 'whatsapp_accounts', 'conversations', 'conversation_participants', 'messages'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->timestamps();
                if ($table === 'roles') {
                    $t->string('name');
                } elseif ($table === 'customers') {
                    $t->string('wa_number')->unique(); $t->string('display_name')->nullable();
                } elseif ($table === 'users') {
                    $t->unsignedBigInteger('role_id')->nullable(); $t->string('phone')->nullable(); $t->string('name'); $t->string('email'); $t->string('password');
                } elseif ($table === 'clients') {
                    foreach (['name', 'whatsapp_meta_no', 'phone_number_id', 'api_version', 'business_account_id', 'permanent_token'] as $c) $t->string($c)->nullable();
                    $t->boolean('Assistan_flag')->default(false); $t->string('AssistantId')->nullable();
                } elseif ($table === 'whatsapp_accounts') {
                    foreach (['phone_number_id', 'display_phone_number', 'verify_token', 'api_version', 'business_account_id', 'access_token_encrypted'] as $c) $t->string($c)->nullable();
                    $t->boolean('is_active')->default(true); $t->json('meta')->nullable();
                } elseif ($table === 'conversations') {
                    $t->string('title'); $t->string('channel'); $t->foreignId('customer_id')->nullable()->constrained('customers');
                    $t->unsignedBigInteger('client_id')->nullable(); $t->json('meta')->nullable();
                } elseif ($table === 'conversation_participants') {
                    $t->unsignedBigInteger('conversation_id'); $t->unsignedBigInteger('user_id'); $t->string('role');
                } else {
                    foreach (['conversation_id', 'whatsapp_account_id', 'sender_id'] as $c) $t->unsignedBigInteger($c)->nullable();
                    foreach (['client_ref', 'sender_number', 'receiver_number', 'sender_name', 'body'] as $c) $t->string($c)->nullable();
                    $t->boolean('is_read')->default(false); $t->integer('isMessageStatus')->default(1); $t->json('meta')->nullable();
                    $t->unique(['whatsapp_account_id', 'client_ref']);
                }
            });
        }
        Event::fake([MessageSent::class, \App\Events\MessageRead::class]);
    }

    private function account($phoneId = 'phone-a', $number = '+15550000001')
    {
        return WhatsAppAccount::create(['phone_number_id' => $phoneId, 'display_phone_number' => $number,
            'is_active' => true, 'verify_token' => 'verify-test', 'api_version' => 'v23.0']);
    }

    private function payload($id = 'inbound-1', $phoneId = 'phone-a')
    {
        return ['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => $phoneId, 'display_phone_number' => '15550000001'],
            'contacts' => [['profile' => ['name' => 'Customer']]],
            'messages' => [['id' => $id, 'from' => '15550000002', 'type' => 'text', 'text' => ['body' => 'Hello']]],
        ]]]]]];
    }

    public function test_inbound_reply_reuses_outbound_conversation_and_broadcasts_once(): void
    {
        $account = $this->account();
        $conv = Conversation::create(['title' => 'Customer', 'channel' => 'whatsapp']);
        Message::create(['conversation_id' => $conv->id, 'whatsapp_account_id' => $account->id,
            'sender_number' => '+15550000001', 'receiver_number' => '+15550000002', 'body' => 'Question', 'client_ref' => 'outbound-1']);
        $this->postJson('/api/wa/webhook', $this->payload())->assertOk();
        $this->postJson('/api/wa/webhook', $this->payload())->assertOk();
        $this->assertSame(2, Message::count());
        $this->assertEquals($conv->id, Message::where('client_ref', 'inbound-1')->firstOrFail()->conversation_id);
        Event::assertDispatchedTimes(MessageSent::class, 1);
    }

    public function test_client_can_reply_using_an_outbound_anchor_without_sending_to_itself(): void
    {
        $account = $this->account();
        $this->account('phone-b', '+15550000003'); // newest active account must not be selected
        $client = Client::create(['name' => 'Business', 'whatsapp_meta_no' => '15550000001',
            'phone_number_id' => 'phone-a', 'api_version' => 'v23.0', 'permanent_token' => 'test-only']);
        $conv = Conversation::create(['title' => 'Customer', 'channel' => 'whatsapp']);
        $anchor = Message::create(['conversation_id' => $conv->id, 'whatsapp_account_id' => $account->id,
            'sender_number' => '+15550000001', 'receiver_number' => '+15550000002', 'body' => 'First', 'client_ref' => 'outbound-1']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'outbound-2']]], 200)]);
        $this->actingAs($client, 'client')->postJson("/client/chat/conversation/{$anchor->id}/send", ['message' => 'Reply'])
            ->assertCreated()->assertJsonPath('conversation_id', $conv->id)->assertJsonPath('receiver_number', '+15550000002');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/phone-a/messages') && $request['to'] === '15550000002');
        $this->getJson("/client/chat/conversation/{$anchor->id}/messages")->assertOk()->assertJsonCount(2);
    }

    public function test_unknown_account_is_rejected_and_both_callback_routes_work(): void
    {
        $this->account();
        $this->postJson('/api/wa/webhook', $this->payload('bad', 'unknown'))->assertStatus(422);
        $this->assertSame(0, Message::count());
        $this->postJson('/webhook/whatsapp', $this->payload())->assertOk();
        $this->assertSame(1, Message::count());
    }

    public function test_verification_requires_the_configured_token(): void
    {
        $this->account();
        $this->get('/api/wa/webhook?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=123')->assertForbidden();
        $this->get('/api/wa/webhook?hub_mode=subscribe&hub_verify_token=verify-test&hub_challenge=123')->assertOk()->assertSee('123');
    }

    public function test_statuses_are_processed_on_the_main_callback_and_do_not_regress(): void
    {
        $account = $this->account();
        $message = Message::create(['whatsapp_account_id' => $account->id, 'client_ref' => 'outbound-1',
            'sender_number' => '+15550000001', 'receiver_number' => '+15550000002']);
        $payload = $this->payload();
        $value =& $payload['entry'][0]['changes'][0]['value'];
        unset($value['messages']);
        $value['statuses'] = [['id' => 'outbound-1', 'status' => 'read'], ['id' => 'outbound-1', 'status' => 'delivered']];
        $this->postJson('/api/wa/webhook', $payload)->assertOk();
        $this->assertEquals(3, $message->fresh()->isMessageStatus);
        $this->assertSame(1, Message::count());
        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_client_cannot_send_using_another_clients_message(): void
    {
        $client = Client::create(['name' => 'Unrelated', 'whatsapp_meta_no' => '+15550000009']);
        $message = Message::create(['sender_number' => '+15550000001', 'receiver_number' => '+15550000002']);
        Http::fake();
        $this->actingAs($client, 'client')->postJson("/client/chat/conversation/{$message->id}/send", ['message' => 'Not mine'])->assertNotFound();
        Http::assertNothingSent();
    }
    public function test_same_customer_has_separate_threads_for_different_business_accounts(): void
    {
        $this->account();
        $this->account('phone-b', '+15550000003');
        $this->postJson('/api/wa/webhook', $this->payload())->assertOk();
        $this->postJson('/api/wa/webhook', $this->payload('inbound-b', 'phone-b'))->assertOk();
        $this->assertSame(2, Conversation::count());
        $this->assertNotEquals(Message::first()->conversation_id, Message::latest('id')->first()->conversation_id);
        $this->assertEquals('+15550000003', Message::latest('id')->first()->receiver_number);
    }

    public function test_legacy_digit_only_history_is_visible(): void
    {
        $client = Client::create(['name' => 'Business', 'whatsapp_meta_no' => '+15550000001']);
        $conv = Conversation::create(['title' => 'Legacy', 'channel' => 'whatsapp']);
        $id = DB::table('messages')->insertGetId(['conversation_id' => $conv->id,
            'sender_number' => '15550000001', 'receiver_number' => '15550000002',
            'body' => 'Legacy', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($client, 'client')->getJson("/client/chat/conversation/{$id}/messages")
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.sender_type', 'self');
        $this->getJson('/client/chat')->assertOk()->assertJsonCount(1);
    }

    public function test_failed_meta_send_is_reported_without_saving_a_message(): void
    {
        $account = $this->account();
        $client = Client::create(['name' => 'Business', 'whatsapp_meta_no' => '+15550000001',
            'phone_number_id' => 'phone-a', 'api_version' => 'v23.0']);
        $conv = Conversation::create(['title' => 'Customer', 'channel' => 'whatsapp']);
        $anchor = Message::create(['conversation_id' => $conv->id, 'whatsapp_account_id' => $account->id,
            'sender_number' => '+15550000002', 'receiver_number' => '+15550000001']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Token expired']], 401)]);
        $this->actingAs($client, 'client')->postJson("/client/chat/conversation/{$anchor->id}/send", ['message' => 'Reply'])
            ->assertUnauthorized()->assertJsonPath('error', 'Token expired');
        $this->assertSame(1, Message::count());
    }

    public function test_configured_webhook_secret_rejects_invalid_signatures(): void
    {
        config(['services.whatsapp.app_secret' => 'test-secret']);
        $this->account();
        $this->postJson('/api/wa/webhook', $this->payload())->assertForbidden();
        $this->assertSame(0, Message::count());
    }

    public function test_chat_page_renders_the_conversation_id_separately_from_the_message_anchor(): void
    {
        $client = Client::create(['name' => 'Business', 'whatsapp_meta_no' => '+15550000001']);
        $conv = Conversation::create(['title' => 'Customer', 'channel' => 'whatsapp']);
        $message = Message::create(['conversation_id' => $conv->id,
            'sender_number' => '+15550000002', 'receiver_number' => '+15550000001', 'body' => 'Hello']);
        $this->actingAs($client, 'client')->get('/client/chat')->assertOk()
            ->assertSee('data-conversation-id="'.$conv->id.'"', false)
            ->assertSee('data-id="'.$message->id.'"', false);
    }

    public function test_api_send_and_inbound_reply_use_the_selected_account_and_same_thread(): void
    {
        $account = $this->account();
        $this->account('phone-b', '+15550000003');
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'api-outbound']]], 200)]);
        $this->postJson('/api/wa/send', ['phone_number_id' => 'phone-a', 'to' => '+15550000002', 'text' => 'Question'])->assertOk();
        Http::assertSent(fn ($request) => str_contains($request->url(), '/phone-a/messages'));
        $outbound = Message::where('client_ref', 'api-outbound')->firstOrFail();
        $this->assertEquals($account->id, $outbound->whatsapp_account_id);
        $this->postJson('/api/wa/webhook', $this->payload())->assertOk();
        $this->assertSame(1, Conversation::count());
        $this->assertEquals($outbound->conversation_id, Message::where('client_ref', 'inbound-1')->firstOrFail()->conversation_id);
    }

    public function test_valid_webhook_signature_is_accepted(): void
    {
        config(['services.whatsapp.app_secret' => 'test-secret']);
        $this->account();
        $body = json_encode($this->payload());
        $this->call('POST', '/api/wa/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-secret'),
        ], $body)->assertOk();
        $this->assertSame(1, Message::count());
    }

}
