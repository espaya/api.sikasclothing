<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'product';

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag', 'product_id', 'tag_id');
    }

    // protected $casts = [
    //     'color' => 'array'
    // ];

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
        'featured',
        'barcode',
        'slug',
        'sku',
        'discount'
    ];
}
