<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    // Define the table name if it's not the default 'users'
    protected $table = 'users';

    // Define the fillable columns to allow mass assignment
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password', // Add other user-specific columns here
    ];

    // Define the hidden attributes (e.g., password) to exclude from JSON responses
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Define the cast types for attributes (e.g., for casting to array, JSON, etc.)
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // You can define relationships, such as with conversations or messages if needed.
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id');
    }

    // Define the relationship with messages if needed
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');  // If user sends messages
    }

    // Function to create a user (if needed in your logic)
    public static function createUser($name, $email, $phone, $password)
    {
        return self::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => bcrypt($password),
        ]);
    }
    public static function ensureForWhatsApp(string $phone): self
    {
        $e164 = preg_replace('/\D+/', '', $phone);

        return static::firstOrCreate(
            ['phone' => $e164],
            [
                'name'     => 'WA ' . substr($e164, -4),
                'email'    => 'wa_' . $e164 . '@example.local',
                'password' => bcrypt(\Illuminate\Support\Str::random(24)),
            ]
        );
    }
}
