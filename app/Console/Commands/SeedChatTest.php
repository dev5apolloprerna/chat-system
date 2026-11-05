<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedChatTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-chat-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conv = \App\Models\Conversation::firstOrCreate(
            ['type'=>'direct','title'=>'919999999999'],
            ['created_by'=>1]
        );

        \App\Models\ConversationParticipant::firstOrCreate(['conversation_id'=>$conv->id,'user_id'=>1],['role'=>'admin']);
        \App\Models\ConversationParticipant::firstOrCreate(['conversation_id'=>$conv->id,'user_id'=>2],['role'=>'member']);

        $msg = \App\Models\Message::create([
            'id'=>\Illuminate\Support\Str::ulid()->toBase32(),
            'conversation_id'=>$conv->id,
            'sender_id'=>1,
            'client_ref'=>\Illuminate\Support\Str::uuid(),
            'body'=>'Test store',
            'meta'=>['debug'=>true]
        ]);

        \App\Models\MessageReceipt::create([
            'message_id'=>$msg->id,
            'user_id'=>2,
            'status'=>'sent'
        ]);

        $this->info('Test chat seeded!');
    }

}
