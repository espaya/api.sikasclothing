<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'products';

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }


    protected $fillable = [
        'product_name',
        'category',
        'sku',
        'tags',
        'gender',
        'brand',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'stock_status',
        'color',
        'material',
        'fit_type',
        'size',
        'gallery',
        'slug',
        'status',
        'featured'
    ];
}
