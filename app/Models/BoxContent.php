<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoxContent extends Model
{
    protected $fillable = [
        'subscription_id',
        'box_id',
    ];

    public function box(): HasOne
    {
        return $this->hasOne('App\Models\Box','id','box_id');

    }

    public function boxHasProducts(): HasMany
    {
        return $this->hasMany('App\Models\BoxContentHasProduct','box_content_id');

    }
}
