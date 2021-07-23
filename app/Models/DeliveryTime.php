<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryTime extends Model
{
    protected $fillable = [
        'description',
        'delivery_interval_id',
        'time_from',
        'time_to',
    ];

    public function intervals(): HasOne
    {
        return $this->hasOne('App\Models\DeliveryInterval', 'id', 'delivery_interval_id');
    }
}
