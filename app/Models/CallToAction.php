<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallToAction extends Model
{
    protected $table = "call_to_action";

    protected $fillable = [
        'title',
        'subtitle',
        'btn_text',
        'btn_url',
        'bg_image'
    ];
}
