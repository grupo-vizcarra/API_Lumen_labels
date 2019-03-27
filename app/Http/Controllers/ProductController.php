<?php

namespace App\Http\Controllers;
use App\Product;
use App\ProductAux;
use Illuminate\Http\Request;

use Laravel\Lumen\Routing\Controller as BaseController;

class ProductController extends BaseController{
    public function precios(Request $request){
        if($request->has(['product', 'price_id'])){
            if(count($request->price_id)<2){
                return response("Debe seleccionar al menos 2 precios", 406);
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
            foreach ($product->prices as $price) {
                if(strcmp($clave, "".$price->pivot->pp_item) != 0){
                    unset($product->prices[$i]);
                }else{
                    if($price->lp_desc == "MEN"){
                        $menudeo = $price->pivot->pp_price+$precio_adicional;
                    }else if($price->lp_desc == "MAY"){
                        $mayoreo = $price->pivot->pp_price+$precio_adicional;
                    }else if($price->lp_desc =="MED"){
                        $media = $price->pivot->pp_price+$precio_adicional;
                    }else{
                        $caja = $price->pivot->pp_price+$precio_adicional;
                    }
                }
                $i++;
            }
            $type = $this->type($menudeo, $mayoreo, $media);
            if($type=='off'){
                $precios = array();
                array_push($precios, array("idlist" => null, "labprint" => "OFERTA", "price" => $menudeo));
                return response()->json([
                    'type' => $type,
                    "tool" => $extencion,
                    "item" => $product->pro_code,
                    "scode" => $product->pro_shortcode,
                    "ipack" => $product->pro_innerpack,
                    "prices" =>$precios
                ]);
            }else if($type=='my'){
                $precios = array();
                array_push($precios, array("idlist" => null, "labprint" => "MAYOREO", "price" => $mayoreo));
                return response()->json([
                    'type' => $type,
                    "tool" => $extencion,
                    "item" => $product->pro_code,
                    "scode" => $product->pro_shortcode,
                    "ipack" => $product->pro_innerpack,
                    "prices" => $precios
                ]);
            }else{
                $precios = array();
                foreach ($request->price_id as $price) {
                    if($price==1){
                        array_push($precios, array("idlist" => 1, "labprint" => "MAY", "price" => $mayoreo));
                    }else if($price==2){
                        array_push($precios, array("idlist" => 2, "labprint" => "MEN", "price" => $menudeo));
                    }else if($price==3){
                        array_push($precios, array("idlist" => 3, "labprint" => "DOC", "price" => $media));
                    }else if($price==4){
                        array_push($precios, array("idlist" => 4, "labprint" => "CJA", "price" => $caja));
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
