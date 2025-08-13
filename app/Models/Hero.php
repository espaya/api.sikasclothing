<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hero extends Model
{
    protected $table = 'hero';
    protected $fillable = [
        'title', 
        'subtitle', 
        'text', 
        'img', 
        'btn_text', 
        'btn_link'
    ];
}
