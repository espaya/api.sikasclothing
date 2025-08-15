<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
        'title',
        'source_type',
        'source_id',
        'custom_url',
        'location',
        'parent_id',
        'order',
        'is_active',
        'child_type',
        'children'
    ];

    public function childrenRecursive()
    {
        return $this->children()
            ->where('is_active', 1)
            ->orderBy('order')
            ->with('category', 'childrenRecursive');
    }


    public function category()
    {
        return $this->belongsTo(Category::class, 'source_id');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }
}
