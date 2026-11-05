<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // one channel for all, you can make it private for per-user chats
        return new PrivateChannel('chat');
    }

    public function broadcastWith()
    {
        return [
            'id'      => $this->message->id,
            'user'    => $this->message->sender->name ?? 'Unknown',
            'body'    => $this->message->body,
            'time'    => $this->message->created_at->toDateTimeString(),
        ];
    }
}
