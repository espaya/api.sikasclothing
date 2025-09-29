<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostTag extends Model
{
    protected $fillable = ['post_id', 'tag'];
    protected $table = 'post_tag';

    public function blog()
    {
        return $this->belongsTo(Blog::class, 'post_id');
    }

    protected $casts = [
        'tags' => 'array', // auto encode/decode JSON
    ];
}
