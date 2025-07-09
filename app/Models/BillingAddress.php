<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAddress extends Model
{
    protected $table = 'billing_address';

    protected $fillable = [
        'firstname',
        'lastname',
        'company_name',
        'country',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'phone',
        'email',
        'default',
        'userID'
    ];

    protected $casts = [
        'default' => 'boolean',
    ];
}
