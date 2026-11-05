<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAccount extends Model
{
    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'business_account_id',
        'phone_number_id',
        'display_phone_number',
        'api_version',
        'verify_token',
        'access_token_encrypted',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    // If you later store encrypted token with encrypt(), this getter reads it
    public function getAccessTokenAttribute(): ?string
    {
        if (!$this->access_token_encrypted) return null;
        try { return decrypt($this->access_token_encrypted); } catch (\Throwable $e) { return $this->access_token_encrypted; }
    }

    public static function byPhoneNumberId(string $phoneNumberId): ?self
    {
        return static::where('phone_number_id', $phoneNumberId)
                     ->where('is_active', true)
                     ->first();
    }
}
