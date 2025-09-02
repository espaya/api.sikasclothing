<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        "shipping_location",
        "shipping_cost", 
        "shipping_method",
        "status",
        "estimated_delivery_time",
        "weight_limit",
        "notes"
    ];
}
