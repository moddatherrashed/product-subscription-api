<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoxContentHasProduct extends Model
{
    protected $fillable = [
        'box_content_id',
        'product_id',
    ];

    public function product(): HasOne
    {
        return $this->hasOne('App\Models\Product','id','product_id');

    }

}
