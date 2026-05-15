<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    protected $fillable = ['version', 'title', 'released_at', 'notes', 'kind'];

    protected $casts = [
        'released_at' => 'date',
    ];
}
