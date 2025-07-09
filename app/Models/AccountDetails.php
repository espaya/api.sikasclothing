<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDetails extends Model
{
    protected $table = 'account_details';

    protected $fillable = [
        'firstname',
        'lastname',
        'display_name',
        'userID'
    ];
}
