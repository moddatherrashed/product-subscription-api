<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $fillable = [
        'title',
        'price',
        'size',
    ];
    public function allBoxes(): HasMany
    {
        return $this->hasMany('App\Models\BoxContent', 'box_id', 'id');

    }
}
