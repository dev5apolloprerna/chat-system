<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'user_id', 'role'];

    // Define the relationship with the conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Define the relationship with the user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

