<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadToDeal extends Model
{
    use HasFactory;

    protected $fillable = ['lead_id', 'deal_id'];
}
