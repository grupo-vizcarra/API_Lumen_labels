<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDF;

class PdfController extends Controller{
    public function createPdf(Request $request){
        $impresora = $request->printer;
        $tickets = $request->tickets;
        if(sizeof($tickets)<1){
            return response("No hay tickets por imprimir", 402);
        }
        if($request->isPrice){
            return $this->pdfTienda($request->tickets, $request->isPack);
        }else{
            return $this->pdfBodega($request->tickets, $request->isPack);
        }
    }
    public function pdfTienda($tickets, $isPack){
        //Se capturan los datos que llegan del front
        //Variables de nuestras distintos tipos de tickets
        $std = [];
        $off = [];
        //Se clasifican los distintos tickets
        foreach($tickets as $ticket){
            if($ticket["type"]=="off"){
                array_push($off, $ticket);
            }else{
                array_push($std, $ticket);
            }
        }

        PDF::SetTitle('Etiquetas de bodega');
        PDF::setPrintHeader(false);
        PDF::setPrintFooter(false);
        PDF::SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        PDF::SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        PDF::setImageScale(PDF_IMAGE_SCALE_RATIO);
        PDF::setCellMargins(0,0,0,0);

        // FORMATO PARA HOJAS DEL PDF
        //$labelsize = sizeof($tickets);
        $flprice=10;// tamaño de nombre de precio para todos
        $fitem=28; // tamaño de fuente del item para lapicera
        $fscode=20;// tamaño de fuente del coigo corto
        $fprice=31;// tamaño de fuente del precio para lapicera

        //  columnas         //filas (por hoja)    //max celdas (por hoja)
        $maxcellperrow=2;    $maxrowsperpage=4;    $maxcellsperpage=$maxcellperrow*$maxrowsperpage;
        $totalwidth=186;
        $total_std = ceil(count($std)/8);
        $total_off = ceil(count($off)/8);
        $totalPages=1;
        $cellONrow=1;// celdas en fila
        $rowOnPage=1;// celdas en tabla
        $cellOnDoc=1;// celdas totales del documento
        $cellOnPage=1;// numero de celda en la pagina actual
        $movetop=18;// distancia en eje "Y" de la cestrella
        $moveleft=10; // distancia en eje "X" de la estrella
        $wcll=$totalwidth/$maxcellperrow; // ancho de la celda
        $hcll=63; //alto de la celda
        $border=0; //borde las celdas
        PDF::AddPage();
        PDF::setCellPaddings(7,5,7,5);
        $headerpage='<div style="color:#00ba34;font-size:24px;">Hoja VERDE ('.$totalPages.')</div>';
        PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
        $mask = PDF::Image(__DIR__.'./resources/img/STAR12.png', 0, 0, 0, '', '', '', '', false, 700, '', true);
        PDF::setCellPaddings(5,5,5,5);
        for($i=0; $i<count($std); $i++){
            if($i==0){
                $moveleft+=0;
            }else if($i%2==1){
                $moveleft+=100;
            }else{
                $moveleft-=100;
                $movetop+=63;
            }
            $pz = '';
            if($isPack){
                $pz.= ' | '.$std[$i]['ipack'].' pz';
            }
            //$totalPages +=1;
            if($i%8==0 && $i!=0){
                $cellONrow=1;// celdas en fila
                $rowOnPage=1;// celdas en tabla
                $cellOnDoc=1;// celdas totales del documento
                $cellOnPage=1;// numero de celda en la pagina actual
                $movetop=18;// distancia en eje "Y" de la estrella
                $moveleft=10; // distancia en eje "X" de la estrella
                $wcll=$totalwidth/$maxcellperrow; // ancho de la celda
                $hcll=63; //alto de la celda
                $border=0; //borde las celdas
                PDF::AddPage();
                PDF::setCellPaddings(7,5,7,5);
                $headerpage='<div style="color:#00ba34;font-size:24px;">Hoja VERDE ('.$totalPages.')</div>';
                PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
                $mask = PDF::Image(__DIR__.'./resources/img/STAR12.png', 0, 0, 0, '', '', '', '', false, 700, '', true);
                PDF::setCellPaddings(5,5,5,5);
            }
            $tool_string = ($std[$i]['tool'] ? "+".$std[$i]['tool'] : "");
            $font_size = (strlen($tool_string)>0 ? 35: 50);
            $font_size = (strlen($tool_string)>1 ? 30: 50);
                $maindts='<table border="0">
							<tr>
								<td style="font-size:'.$font_size.'px;"><b>'.$std[$i]['scode'].$tool_string.'</b></td>
							</tr>
							<tr>
								<td style="font-size:15px;"><b>'.$std[$i]['item'].$pz.'</b></td>
							</tr>
                        </table>';
                        $prices_pdf = '';
                foreach($std[$i]['prices'] as $price){
                    if($price['labprint']=='OFERTA'){
                        $prices_pdf.= '<b style="font-size:'.($fprice).'px;">OFERTA</b><br>
                        <b style="font-size:'.($fprice+10).'px;">'.($price['price']).'</b>';
                    }else if($price['labprint']=='MAYOREO'){
                        $prices_pdf.= '<b style="font-size:'.($fprice).'px;">MAYOREO</b><br>
                        <b style="font-size:'.($fprice+10).'px;">'.($price['price']).'</b>';
                    }else{
                        $prices_pdf.= '<b style="font-size:'.($fprice).'px;">'.($price['labprint']).'</b>
                        <b style="font-size:'.($fprice).'px;">'.($price['price']).'</b><br>';
                    }
                }
                $pricings = '<table border="0"><tr>
                        <td>'.$prices_pdf.'
                        </td>
                    </tr></table>';
                PDF::setCellPaddings(25,12.5,25,10);
                PDF::Image(__DIR__.'./resources/img/STAR12.png', $moveleft, $movetop, $wcll, $hcll, '', '', '', false, 300, '', false, $mask);
                PDF::writeHTMLCell($wcll, $hcll, $moveleft, $movetop-8, $maindts.$pricings, $border, 0, 0, true, 'C',true);
            }
            if(count($off)>0){
                $movetop=18;// distancia en eje "Y" de la cestrella
                $moveleft=10; // distancia en eje "X" de la estrella
                PDF::AddPage();
                PDF::setCellPaddings(7,5,7,5);
                $headerpage='<div style="color:orange;font-size:24px;">Hoja NARANJA ('.$totalPages.')</div>';
                PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
            }
            for($i=0; $i<count($off); $i++){
                if($i==0){
                    $moveleft+=0;
                }else if($i%2==1){
                    $moveleft+=100;
                }else{
                    $moveleft-=100;
                    $movetop+=63;
                }
                $pz = '';
                if($isPack){
                    $pz.= ' | '.$off[$i]['ipack'];
                }
                //$totalPages +=1;
                if($i%8==0 && $i!=0){
                    $cellONrow=1;// celdas en fila
                    $rowOnPage=1;// celdas en tabla
                    $cellOnDoc=1;// celdas totales del documento
                    $cellOnPage=1;// numero de celda en la pagina actual
                    $movetop=18;// distancia en eje "Y" de la estrella
                    $moveleft=10; // distancia en eje "X" de la estrella
                    $wcll=$totalwidth/$maxcellperrow; // ancho de la celda
                    $hcll=63; //alto de la celda
                    $border=0; //borde las celdas
                    PDF::AddPage();
                    PDF::setCellPaddings(7,5,7,5);
                    $headerpage='<div style="color:orange;font-size:24px;">Hoja NARANJA ('.$totalPages.')</div>';
                    PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
                    $mask = PDF::Image(__DIR__.'./resources/img/STAR12.png', 0, 0, 0, '', '', '', '', false, 700, '', true);
                    PDF::setCellPaddings(5,5,5,5);
                }
                $tool_string = ($off[$i]['tool'] ? "+".$off[$i]['tool'] : "");
                $font_size = (strlen($tool_string)>0 ? 35: 50);
                $font_size = (strlen($tool_string)>1 ? 30: 50);
                    $maindts='<table border="0">
                                <tr>
                                    <td style="'.$font_size.'px"><b>'.$off[$i]['scode'].$tool_string.'</b></td>
                                </tr>
                                <tr>
                                    <td style="font-size:15px;"><b>'.$off[$i]['item'].$pz.'</b></td>
                                </tr>
                            </table>';
                            $prices_pdf = '';
                    foreach($off[$i]['prices'] as $price){
                        if($price['labprint']=='OFERTA'){
                            $prices_pdf.= '<b style="font-size:'.($fprice).'px;">OFERTA</b><br>
                            <b style="font-size:'.($fprice+10).'px;">'.($price['price']).'</b>';
                        }else if($price['labprint']=='MAYOREO'){
                            $prices_pdf.= '<b style="font-size:'.($fprice).'px;">MAYOREO</b><br>
                            <b style="font-size:'.($fprice+10).'px;">'.($price['price']).'</b>';
                        }else{
                            $prices_pdf.= '<b style="font-size:'.($fprice).'px;">'.($price['labprint']).'</b>
                            <b style="font-size:'.($fprice).'px;">'.($price['price']).'</b><br>';
                        }
                    }
                    $pricings = '<table border="0"><tr>
                            <td>'.$prices_pdf.'
                            </td>
                        </tr></table>';
                    PDF::setCellPaddings(25,12.5,25,10);
                    PDF::Image(__DIR__.'./resources/img/STAR12.png', $moveleft, $movetop, $wcll, $hcll, '', '', '', false, 300, '', false, $mask);
                    PDF::writeHTMLCell($wcll, $hcll, $moveleft, $movetop-8, $maindts.$pricings, $border, 0, 0, true, 'C',true);
                }
            $nameFile = time().'.pdf';
        PDF::Output(__DIR__.'/../../../files/'.$nameFile, 'F');
        return response()->json(["archivo" => $nameFile, "off" => ceil(count($off)/8), "std"=> ceil(count($std)/8)]);
    }

    public function pdfBodega($tickets, $isPack){
        //Se capturan los datos que llegan del front

        
        PDF::SetTitle('Etiquetas de bodega');
        PDF::setPrintHeader(false);
        PDF::setPrintFooter(false);
        PDF::SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        PDF::SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        PDF::setImageScale(PDF_IMAGE_SCALE_RATIO);
        PDF::setCellMargins(0,0,0,0);

        // FORMATO PARA HOJAS DEL PDF
        //$labelsize = sizeof($tickets);
        $flprice=10;// tamaño de nombre de precio para todos
        $fitem=28; // tamaño de fuente del item para lapicera
        $fscode=20;// tamaño de fuente del coigo corto
        $fprice=40;// tamaño de fuente del precio para lapicera

        //  columnas         //filas (por hoja)    //max celdas (por hoja)
        $maxcellperrow=2;    $maxrowsperpage=4;    $maxcellsperpage=$maxcellperrow*$maxrowsperpage;
        $totalwidth=220;
        $totalPages=1;
        $cellONrow=1;// celdas en fila
        $rowOnPage=1;// celdas en tabla
        $cellOnDoc=1;// celdas totales del documento
        $cellOnPage=1;// numero de celda en la pagina actual
        $movetop=18;// distancia en eje "Y" de la cestrella
        $moveleft=10; // distancia en eje "X" de la estrella
        $wcll=$totalwidth/$maxcellperrow; // ancho de la celda
        $hcll=27; //alto de la celda
        $border=0; //borde las celdas
        PDF::AddPage();
        PDF::setCellPaddings(7,5,7,5);
        //$headerpage='<div style="color:#00ba34;font-size:24px;">Hoja VERDE ('.$totalPages.')</div>';
        //PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
        PDF::setCellPaddings(7,5,7,5);
        for($i=0; $i<count($tickets); $i++){
            if($i==0){
                $moveleft+=0;
            }else if($i%2==1){
                $moveleft+=100;
                $cuadro = '<hr style="border-top: dotted 1px;">';
                PDF::writeHTMLCell(235, 1, -10, $movetop+$hcll-5, $cuadro, $border, 0, 0, true, 'C',true);
            }else{
                $moveleft-=100;
                $movetop+=35;
            }
            //$totalPages +=1;
            if($i==0){
                $cuadro = '<div style="border-left: 1px dashed black;"></div>';
                PDF::writeHTMLCell(.01, 250, $moveleft+100, 10, $cuadro, 1, 0, 0, true, 'C',true);
                $cuadro = '<hr style="border-top: dotted 1px;">';
                PDF::writeHTMLCell(235, 1, -10, $movetop+$hcll-5, $cuadro, $border, 0, 0, true, 'C',true);
            }else{
                if($i%14==1){
                    $cuadro = '<div style="border-left: 1px dashed black;"></div>';
                    PDF::writeHTMLCell(.01, 250, $moveleft, 10, $cuadro, 1, 0, 0, true, 'C',true);
                }
            }
            if($i%14==0 && $i!=0){
                $cellONrow=1;// celdas en fila
                $rowOnPage=1;// celdas en tabla
                $cellOnDoc=1;// celdas totales del documento
                $cellOnPage=1;// numero de celda en la pagina actual
                $movetop=18;// distancia en eje "Y" de la estrella
                $moveleft=10; // distancia en eje "X" de la estrella
                $wcll=$totalwidth/$maxcellperrow; // ancho de la celda
                $hcll=30; //alto de la celda
                $border=0; //borde las celdas
                PDF::AddPage();
                PDF::setCellPaddings(7,5,7,5);
                //$headerpage='<div style="color:#00ba34;font-size:24px;">Hoja VERDE ('.$totalPages.')</div>';
                //PDF::writeHTMLCell(0, 0, '', '', $headerpage, 0, 1, 0, true, 'L',false);
                
            }
            $pz='';
            if($isPack){
                $pz.= ' | '.$tickets[$i]['ipack'] . ' pz';
            }
            $cuadro = '<div style="text-align: center; font-size: 40px; display: inline-block; font-weight: bold;">'.$tickets[$i]['item'].'<br><span style="font-size:25px; text-align: center;">'.$tickets[$i]['scode'].$pz.'</span></div>';
            PDF::writeHTMLCell($wcll, $hcll, $moveleft, $movetop-8, $cuadro, $border, 0, 0, true, 'C',true);
        }
        $nameFile = time().'.pdf';
    PDF::Output(__DIR__.'/../../../files/'.$nameFile, 'F');
    return response()->json(["archivo" => $nameFile, "hojas" => ceil(count($tickets)/14) ]);
}

}