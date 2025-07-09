<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',                // 'percentage' or 'fixed'
        'amount',              // 10 (10%) or 20 (20 currency units)
        'minimum_order_value', // optional minimum cart value to apply
        'maximum_discount',    // optional cap if it's a percentage
        'discount_code',       // e.g., SUMMER10
        'starts_at',
        'ends_at',
        'status',              // active/inactive
        'usage_limit',         // max number of times the code can be used
        'used_count',          // how many times it has already been used
    ];


    public function products()
    {
        return $this->hasMany(Products::class);
    }
}
