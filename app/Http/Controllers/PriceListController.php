<?php

namespace App\Http\Controllers;
use App\PriceList;
use Illuminate\Http\Request;

use Laravel\Lumen\Routing\Controller as BaseController;

class PriceListController extends BaseController{
    public function getAll(){
        $rows = PriceList::all();
        return response()->json($rows);
    }
}