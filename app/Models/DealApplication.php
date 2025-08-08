<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealApplication extends Model
{
    use HasFactory;
    protected $labels;
    protected $products;
    protected $sources;
    protected $fillable = ['labels','application_key','deal_id', 'university_id', 'course', 'stage_id', 'external_app_id', 'name', 'intake', 'created_by'];

    public function getUniversity($id)
    {
        return University::where('id', $id)->first();
    }
    public static function getDealSummary($deals)
    {
        $total = 0;

        foreach($deals as $deal)
        {
            $total += $deal->price;
        }

        return \Auth::user()->priceFormat($total);
    }

    public function labels()
    {
        if($this->labels)
        {
            return Label::whereIn('id', explode(',', $this->labels))->get();
        }

        return false;
    }

    public function products()
    {
        if($this->products)
        {
            return ProductService::whereIn('id', explode(',', $this->products))->get();
        }

        return [];
    }
    public function sources()
    {
        if($this->sources)
        {
            return Source::whereIn('id', explode(',', $this->sources))->get();
        }

        return [];
    }
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_deals', 'deal_id', 'user_id');
    }
    public function stage()
    {
        return $this->hasOne('App\Models\Stage', 'id', 'stage_id');
    }
}
