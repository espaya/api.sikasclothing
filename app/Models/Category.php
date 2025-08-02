<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';

    protected $fillable = [
        'name',             // More specific than 'category'
        'slug',             // SEO-friendly URL
        'description',      // Description of the category
        'image',            // Renamed from 'img' for clarity
        'parent_id',        // For sub-categories (nullable)
        'is_featured',      // Boolean to mark featured category
        'status',           // 'active', 'inactive', etc.
    ];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'category_product', 'product_id', 'category_id');
    }

    protected $casts = [
        'is_featured' => 'boolean',
    ];
}
