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
    
    public static function forWhatsAppPair(User $customer, string $businessNumber): self
    {
        $peer = Message::normalizeNumber($customer->phone);
        $businessNumber = Message::normalizeNumber($businessNumber);
        $existing = Message::where(function ($q) use ($peer, $businessNumber) {
            $q->where(function ($q) use ($peer, $businessNumber) {
                $q->whereIn('sender_number', Message::numberVariants($peer))
                    ->whereIn('receiver_number', Message::numberVariants($businessNumber));
            })->orWhere(function ($q) use ($peer, $businessNumber) {
                $q->whereIn('sender_number', Message::numberVariants($businessNumber))
                    ->whereIn('receiver_number', Message::numberVariants($peer));
            });
        })->latest('id')->first();
        if ($existing && $existing->conversation) return $existing->conversation;
        // conversations.customer_id references customers, not users/clients.
        $contact = Customer::firstOrCreate(['wa_number' => ltrim($peer, '+')], [
            'display_name' => $customer->name,
        ]);
        return static::firstOrCreate([
            'channel' => 'whatsapp', 'customer_id' => $contact->id, 'meta->receiver' => $businessNumber,
        ], [
            'title' => 'WhatsApp: ' . $peer, 'meta' => ['receiver' => $businessNumber],
        ]);
    }

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
