<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brand';

    protected $fillable = [
        'name',
        'logo',
        'description',
        'status',
        'is_featured',
        'website',
        'slug'
    ];
}
