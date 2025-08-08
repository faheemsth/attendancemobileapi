<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailMarketing extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
        'email_content',
        'created_by',
        'tag',
    ];
}
