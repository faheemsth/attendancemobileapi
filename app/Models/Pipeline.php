<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    protected $fillable = [
        'name',
        'created_by',
    ];

    public function stages()
    {
        return $this->hasMany('App\Models\Stage', 'pipeline_id', 'id')->orderBy('order');
    }

    public function ApplicationStage()
    {
        return $this->hasMany('App\Models\ApplicationStage', 'pipeline_id', 'id')->orderBy('order');
    }

    public function leadStages()
    {
        return $this->hasMany('App\Models\LeadStage', 'pipeline_id', 'id')->orderBy('order');
    }
}
