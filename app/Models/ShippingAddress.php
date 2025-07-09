<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $table = 'shipping_address';

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
        'userID'
    ];
}
