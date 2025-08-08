<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competencies extends Model
{
    protected $fillable = [
        'name',
        'type',
        'created_by',
    ];


    public function performance()
    {
        return $this->hasOne('Spatie\Permission\Models\Role', 'id', 'type');
    }
}
