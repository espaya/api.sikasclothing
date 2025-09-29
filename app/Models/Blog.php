<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'blogs';

    protected $fillable = [
        'title',
        'category',
        'tags',
        'content',
        'status',
        'featured_image',
        'slug',
        'publish_date',
        'comments_enabled'
    ];

    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'category');
    }

    public function postTags()
    {
        return $this->hasMany(PostTag::class, 'post_id');
    }

    public function comment()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
