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
        'is_read' => 'boolean',
        'isMessageStatus' => 'integer',
    ];



    public static function normalizeNumber(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number ?? '');
        return $digits === '' ? '' : '+' . $digits;
    }

    public static function numberVariants(?string $number): array
    {
        $canonical = static::normalizeNumber($number);
        return array_values(array_unique([$canonical, ltrim($canonical, '+')]));
    }

    public function getSenderNumberAttribute($value)
    {
        return static::normalizeNumber($value);
    }

    public function getReceiverNumberAttribute($value)
    {
        return static::normalizeNumber($value);
    }

    public function setSenderNumberAttribute($value)
    {
        $this->attributes['sender_number'] = static::normalizeNumber($value);
    }

    public function setReceiverNumberAttribute($value)
    {
        $this->attributes['receiver_number'] = static::normalizeNumber($value);
    }

    // Define the relationship with the Conversation model
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
