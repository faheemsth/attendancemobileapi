<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Region;

class Branch extends Model
{
    protected $fillable = [
        'name','brands', 'created_by', 'branch_manager_id', 'region_id'
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'branch_manager_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
