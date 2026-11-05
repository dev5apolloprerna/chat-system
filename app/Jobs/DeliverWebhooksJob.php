<?php
namespace App\Jobs;
use App\Models\WebhookOutbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class DeliverWebhooksJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $batch = WebhookOutbox::whereNull('delivered_at')
            ->where('next_attempt_at','<=',now())->limit(50)->get();

        foreach ($batch as $row) {
            $endpoint = $row->endpoint;
            $body = json_encode(['event'=>$row->event, 'data'=>$row->payload]);
            $sig  = hash_hmac('sha256', $body, $endpoint->secret);

            try {
                $resp = Http::withHeaders([
                    'Content-Type'=>'application/json',
                    'X-Chat-Signature' => $sig,
                ])->timeout(8)->post($endpoint->url, $body);

                if ($resp->successful()) {
                    $row->update(['delivered_at'=>now()]);
                } else {
                    $row->update([
                        'attempts'=>$row->attempts+1,
                        'last_error'=>$resp->status().' '.$resp->body(),
                        'next_attempt_at'=> now()->addSeconds( 2 ** min($row->attempts, 8) ),
                    ]);
                }
            } catch (\Throwable $e) {
                $row->update([
                    'attempts'=>$row->attempts+1,
                    'last_error'=>$e->getMessage(),
                    'next_attempt_at'=> now()->addSeconds( 2 ** min($row->attempts, 8) ),
                ]);
            }
        }
    }
}
