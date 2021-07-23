<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected
        $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone_number',
        'stripe_id',
        'card_brand',
        'card_last_four',
        'pm_id',
        'app_version',
        'role',
        'platform',
        'fcm_token',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected
        $hidden = [
        'password',
    ];


    public
    function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public
    function getJWTCustomClaims(): array
    {
        return [];
    }


    public function subscriptions(): HasOne
    {
        return $this->hasOne('App\Models\Subscription', 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany('App\Models\Address', 'user_id');
    }

}
