<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryInterval extends Model
{
    protected $table = 'delivery_interval';
    protected $fillable = [
        'zip_code',
        'city',
    ];
}
