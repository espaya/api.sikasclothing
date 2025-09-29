<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    protected $table = 'post_category';

    protected $fillable = [
        'category_name',
        'status',
        'description',
        'featured_image'
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
