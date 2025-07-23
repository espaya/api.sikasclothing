<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = ['name'];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'product_tag', 'product_id', 'tag_id');
    }
}
