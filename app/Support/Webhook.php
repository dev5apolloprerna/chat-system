<?php

namespace App\Support;
use App\Models\WebhookEndpoint;
use App\Models\WebhookOutbox;

class Webhook
{
    public static function queue(string $event, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('active', true)
            ->whereJsonContains('events', $event)->get();

        foreach ($endpoints as $ep) {
            WebhookOutbox::create([
                'event' => $event,
                'payload' => $payload,
                'endpoint_id' => $ep->id,
                'attempts' => 0,
                'next_attempt_at' => now(),
            ]);
        }

        dispatch(new \App\Jobs\DeliverWebhooksJob())->onQueue('webhooks');
    }
}
