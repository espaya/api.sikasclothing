<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_item';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total'
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
