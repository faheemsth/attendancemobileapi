<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DealTask extends Model
{
    protected $fillable = [
        'deal_id','name','date','time','priority','status','assigned_to','branch_id','created_by','due_date','is_swap'
    ];

    public static $priorities = [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
    ];
    public static $status = [
        0 => 'On Going',
        1 => 'Completed'
    ];

    public function user_deals()
    {
        return $this->hasMany(UserDeal::class);
    }

    public function deal_tasks()
    {
        return $this->hasMany(DealTask::class);
    }
}
