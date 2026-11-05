<?php

namespace App\Services;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?WhatsAppAccount $account;

    public function __construct()
    {
        // Always work with the active account
        $this->account = WhatsAppAccount::where('is_active', true)->orderByDesc('id')->first();
    }

    public function currentAccount(): ?WhatsAppAccount
    {
        return $this->account;
    }

    protected function apiUrl(string $path = '/messages'): string
    {
        $phoneNumberId = $this->account?->phone_number_id ?? config('services.whatsapp.phone_number_id');
        // dd($phoneNumberId);
        $apiVersion    = $this->account?->api_version ?? 'v18.0';

        return "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}" . $path;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->account?->access_token ?? config('services.whatsapp.token')),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Send a plain text message
     */
    public function sendText(string $to, string $message): array
    {
        $toDigits = preg_replace('/\D+/', '', $to);
        $toE164   = '+' . $toDigits;
        // dd($toE164);

        // Prevent sending to self (account number)
        if ($this->senderNumberE164() && $this->senderNumberE164() === $toE164) {
            return ['error' => 'Recipient cannot be the sender number'];
        }

        $resp = Http::withHeaders($this->headers())
            ->post($this->apiUrl('/messages'), [
                'messaging_product' => 'whatsapp',
                'to'   => ltrim($toE164, '+'),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (!$resp->successful()) {
            Log::error('WhatsApp sendText failed', [
                'to'    => $toE164,
                'code'  => $resp->status(),
                'error' => $resp->body(),
            ]);
        }

        return $resp->json();
    }

    /**
     * Send a template message
     */
    public function sendTemplate(string $to, string $templateName, string $language = 'en_US', array $components = []): array
    {
        $toDigits = preg_replace('/\D+/', '', $to);
        $toE164   = '+' . $toDigits;

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'       => ltrim($toE164, '+'),
            'type'     => 'template',
            'template' => [
                'name'     => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $resp = Http::withHeaders($this->headers())
            ->post($this->apiUrl('/messages'), $payload);

        if (!$resp->successful()) {
            Log::error('WhatsApp sendTemplate failed', [
                'to'    => $toE164,
                'code'  => $resp->status(),
                'error' => $resp->body(),
            ]);
        }

        return $resp->json();
    }

    public function getSenderNumber(): ?string
    {
        return $this->senderNumberE164();
    }

    protected function senderNumberE164(): ?string
    {
        $acc = $this->currentAccount();
        return $acc && $acc->display_phone_number
            ? ('+' . ltrim($acc->display_phone_number, '+'))
            : null;
    }
}
