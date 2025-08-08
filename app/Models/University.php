<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    use HasFactory;
    protected $appends =['country_code'];

    public function course()
    {
        return $this->hasOne(Course::class);
    }

    public function getCountryCodeAttribute() {
        
        $country = $this->country;
        $ct = Country::where('name',$country)->first();
        return strtolower($ct->country_code ?? '');
    }
}
