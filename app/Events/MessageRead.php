<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $messageIds;

    public function __construct($conversationId, $messageIds)
    {
        $this->conversationId = $conversationId;
        $this->messageIds = $messageIds;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->conversationId);
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_ids' => $this->messageIds,
        ];
    }
}
