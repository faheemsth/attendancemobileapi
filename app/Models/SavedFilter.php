<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    protected $fillable = [
        'filter_name',
        'module',
        'url',
        'count',
        'created_by',
    ];
}
