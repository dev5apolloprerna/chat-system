<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MessageReceipt extends Model
{
    protected $fillable = ['message_id','user_id','status','delivered_at','seen_at'];
    protected $casts = ['delivered_at'=>'datetime','seen_at'=>'datetime'];
}
