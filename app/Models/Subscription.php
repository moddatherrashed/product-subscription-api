<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

class Subscription extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected
        $fillable = [
        'status',
        'created_at',
        'user_id',
        'stripe_sub_id',
        'delivery_interval_id',
        'delivery_time_id'
    ];

    public function times(): HasOne
    {
        return $this->hasOne('App\Models\DeliveryTime', 'id', 'delivery_time_id');
    }

    public function boxContents(): HasOne
    {
        return $this->hasOne('App\Models\BoxContent', 'subscription_id');
    }

    // this for cashier insert statement
    public function items(): HasOne
    {
        return $this->hasOne('App\Models\BoxContent', 'subscription_id');
    }
}
