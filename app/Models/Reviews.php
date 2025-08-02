<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'name',
        'email',
        'rating',
        'remember',
        'review',
        'product_id',
        'status'
    ];
}
