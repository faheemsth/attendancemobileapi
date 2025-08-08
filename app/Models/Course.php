<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;


    public function university()
    {
        return $this->belongsTo(University::class);
    }


    public function courselevel()
    {
        return $this->belongsTo(CourseLevel::class);
    }

    public function courseduration()
    {
        return $this->belongsTo(CourseDuration::class);
    }
}
