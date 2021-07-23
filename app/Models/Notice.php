<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'notices';
    protected $fillable = [
        'title',
        'description',
        'is_hidden',
        'updated_at',
    ];
}
