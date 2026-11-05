<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use Notifiable;
    public $table = 'clients';
    protected $fillable = [
        'name',
        'password',
        'mobile',
        'whatsapp_meta_no',
        'email',
        'address',
        'state',
        'city',
        'pincode',
        'udf_1',
        'udf_2',
        'permanent_token',
        'iStatus',
        'isDelete',
        'strIP',
        'created_at',
        'updated_at',
        'api_version',
        'phone_number_id',
        'business_account_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
