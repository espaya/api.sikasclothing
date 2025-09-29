<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'comment',
        'comment_name',
        'comment_email',
        'post_id',
        'status'
    ];
}
