<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WebhookOutbox extends Model
{
    protected $fillable = ['event','payload','endpoint_id','attempts','next_attempt_at','last_error','delivered_at'];
    protected $casts = ['payload'=>'array','delivered_at'=>'datetime','next_attempt_at'=>'datetime'];
    public function endpoint(){ return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id'); }
}