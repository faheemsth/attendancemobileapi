<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLevel extends Model
{
    protected $table = 'courselevels';
    use HasFactory;

    public function course()
    {
        return $this->hasOne(Course::class);
    }
}
