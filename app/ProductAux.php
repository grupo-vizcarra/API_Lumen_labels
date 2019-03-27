<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductAux extends Model{
    protected $table = 'products';
    protected $primaryKey = 'proid';
    protected $fillable = ['procode', 'pro_shortcode'];
}