<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;


     protected $fillable = [
        'title',
        'channel',
        'customer_id',
        'client_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
    
    // Define the relationship with the participants (many-to-many)
    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }
    
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id', 'id')->orderBy('id', 'asc');
    }
}
