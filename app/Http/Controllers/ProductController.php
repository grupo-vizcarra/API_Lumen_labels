<?php

namespace App\Http\Controllers;
use App\Product;
use App\ProductAux;
use Illuminate\Http\Request;
use App\PriceList;

use Laravel\Lumen\Routing\Controller as BaseController;

class ProductController extends BaseController{
    public function priceList(){
        return response()->json(PriceList::all());
    }
    public function precios(Request $request){
        if($request->has(['product', 'price_id'])){
            if($request->isPrice){
                if(count($request->price_id)<2){
                    return response("Debe seleccionar al menos 2 precios", 406);
                }
            }
            $arreglo= explode('+', $request->product);
            $clave = $arreglo[0];
            if(count($arreglo)==2){
                $extencion = strtoupper($arreglo[1]);
            }else{
                $extencion ='';
            }
            $precio_adicional = 0;
            if(strlen($extencion)>0){
                $precio_adicional = -404;
                $claves = $this->claves();
                foreach($claves as $claveAux){
                    if($extencion==$claveAux['clave']){
                        $precio_adicional=$claveAux['precio'];
                    }
                }
                if($precio_adicional==-404){
                    return response("Clave no válida", 405);
                }
            }
            $product = Product::find($clave);
            if(!$product){
                $product = Product::where('pro_shortcode', "".$clave)->get();
                if(!count($product)){
                    return response("Producto no encontrado", 404)->header('Content-Type', 'text/plain');
                }
                $clave = $product[0]->pro_code;
                $product = Product::find($clave);
        }
        $i=0;
        $priceList = $this->priceList();
        //return response()->json($priceList->original);
            foreach ($product->prices as $price) {
                if(strcmp($clave, "".$price->pivot->pp_item) != 0){
                    unset($product->prices[$i]);
                }else{
                    if($price->lp_desc == $priceList->original[0]->lp_desc){
                        $menudeo = $price->pivot->pp_price+$precio_adicional;
                    }else if($price->lp_desc == $priceList->original[1]->lp_desc){
                        $mayoreo = $price->pivot->pp_price+$precio_adicional;
                    }else if($price->lp_desc ==$priceList->original[2]->lp_desc){
                        $media = $price->pivot->pp_price+$precio_adicional;
                    }else{
                        $caja = $price->pivot->pp_price+$precio_adicional;
                    }
                }
                $i++;
            }
            $type = $this->type($menudeo, $mayoreo, $media);
            $precios = array();
            if($type=='off'){
                array_push($precios, array("idlist" => null, "labprint" => "OFERTA", "price" => "$ ".$menudeo));
            }else if($type=='my'){
                array_push($precios, array("idlist" => null, "labprint" => "MAYOREO", "price" => "$ ".$mayoreo));
            }else{
                foreach ($request->price_id as $price) {
                    if($price==$priceList->original[0]->lp_id){
                        array_push($precios, array("idlist" => $priceList->original[0]->lp_id, "labprint" => $priceList->original[0]->lp_desc, "price" => "$ ".$mayoreo));
                    }else if($price==$priceList->original[1]->lp_id){
                        array_push($precios, array("idlist" => $priceList->original[1]->lp_id, "labprint" => $priceList->original[1]->lp_desc, "price" => "$ ".$menudeo));
                    }else if($price==$priceList->original[2]->lp_id){
                        array_push($precios, array("idlist" => $priceList->original[2]->lp_id, "labprint" => $priceList->original[2]->lp_desc, "price" => "$ ".$media));
                    }else if($price==$priceList->original[3]->lp_id){
                        array_push($precios, array("idlist" => $priceList->original[3]->lp_id, "labprint" => $priceList->original[3]->lp_desc, "price" => "$ ".$caja));
                    }
                }
            }
            return response()->json([
                'type' => $type,
                "tool" => $extencion,
                "item" => $product->pro_code,
                "scode" => $product->pro_shortcode,
                "ipack" => $product->pro_innerpack,
                "prices" =>$precios
            ]);
        }
    }

    public function claves(){
        $claves = array(
            array("clave"=>"C", "precio"=>120),
            array("clave"=>"CC", "precio"=>130)
        );
        return $claves;
    }
    public function type($menudeo, $mayoreo, $docena){
        if($menudeo==$mayoreo && $mayoreo==$docena){
            return 'off';
        }else if($mayoreo==$docena){
            return 'my';
        }
        return 'std';
    }
}
