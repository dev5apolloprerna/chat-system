<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'whatsapp_account_id',
        'sender_id',
        'client_ref',
        'sender_number',
        'sender_name',
        'receiver_number',
        'body',
        'meta',
        'is_read',
        'isMessageStatus',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'meta' => 'array',
    ];



    // Define the relationship with the Conversation model
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
