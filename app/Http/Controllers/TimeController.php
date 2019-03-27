<?php

namespace App\Http\Controllers;
use App\PriceList;
use App\Product;
use App\ProductAux;
use App\Migration;
use Illuminate\Http\Request;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class TimeController extends Controller{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
        //
    }

    public function precios(Request $request){
        if($request->has(['product', 'price_id'])){
            if(count($request->price_id)<1){
                return response("Debe seleccionar al menos 2 precios", 200);
            }
            $arreglo= explode('+', $request->product);
            $clave = $arreglo[0];
            if(count($arreglo)==2){
                $extencion = $arreglo[1];
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
                    return response("Clave no válida", 200);
                }
            }
            $product = Product::find($clave);
            if(!$product){
                $product = Product::where('pro_shortcode', "".$clave)->get();
                if(!count($product)){
                    return response("Código proporcionado no válido", 200);
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
                return response()->json([
                    'type' => $type,
                    "tool" => $extencion,
                    "item" => $product->pro_code,
                    "scode" => $product->pro_shortcode,
                    "ipack" => $product->pro_innerpack,
                    "prices" =>array("idlist" => null, "labprint" => "OFERTA", "price" => $menudeo)
                ]);
            }else if($type=='my'){
                return response()->json([
                    'type' => $type,
                    "tool" => $extencion,
                    "item" => $product->pro_code,
                    "scode" => $product->pro_shortcode,
                    "ipack" => $product->pro_innerpack,
                    "prices" =>array("idlist" => null, "labprint" => "MAYOREO", "price" => $mayoreo)
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
