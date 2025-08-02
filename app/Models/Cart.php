<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'color',
        'size',
        'checkout_at'
    ];

    

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

}
