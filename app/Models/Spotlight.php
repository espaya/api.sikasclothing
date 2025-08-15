<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spotlight extends Model
{
    protected $table = 'spotlight';

    protected $fillable = [
        'title',
        'link_text',
        'link_url',
        'bg_color',
        'bg_image',
        'add_to_megamenu'
    ];
}
