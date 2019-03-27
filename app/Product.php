<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model{
    protected $table = 'products';
    protected $primaryKey = 'pro_code';
    public $incrementing = false;
    
    protected $fillable = ['procode', 'pro_shortcode'];

    public function prices(){
        return $this->belongsToMany('App\PriceList', 'product_prices', 'pp_item', 'pp_pricelist')->withPivot('pp_price');
    }
}