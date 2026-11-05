<?php
// app/Models/Customer.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['wa_number','display_name','name','meta','last_inbound_at'];
    protected $casts = ['meta' => 'array', 'last_inbound_at' => 'datetime'];

    public function conversations() { return $this->hasMany(Conversation::class); }
}
