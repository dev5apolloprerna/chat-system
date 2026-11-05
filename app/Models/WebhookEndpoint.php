<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    protected $fillable = ['url','secret','active','events'];
    protected $casts = ['events'=>'array','active'=>'boolean'];
}
