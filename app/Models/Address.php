<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'firstname',
        'lastname',
        'street',
        'zip',
        'city',
        'country',
        'note',
        'user_id'
    ];
}
