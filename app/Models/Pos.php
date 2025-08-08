<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Pos extends Model
{
    protected $fillable = [
        'pos_id',
        'customer_id',
        'warehouse_id',
        'pos_date',
        'category_id',
        'status',
        'shipping_display',
        'created_by',
    ];

    public function customer()
    {

        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }
    public function warehouse()
    {
        return $this->hasOne('App\Models\warehouse', 'id', 'warehouse_id');
    }

    public function posPayment()
    {
        return $this->hasOne('App\Models\PosPayment','pos_id','id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\PosProduct', 'pos_id', 'id');
    }
    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach($this->items as $product)
        {

            $subTotal += ($product->price * $product->quantity);

        }

        return $subTotal;
    }

    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach($this->items as $product)
        {

            $totalDiscount += $product->discount;
        }

        return $totalDiscount;
    }

}



