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
        /*-----------------------
        ---Config pdf document---
        -------------------------*/
        $witdh_page = 200;
        $height_page = 250;
        $font_size_principal = 3.8;
        $font_size_secondary = 1.9;
        $font_size_aux = 1.2;
        PDF::SetCreator('Grupo Vizcarra');
        PDF::SetTitle('Etiquetas');
        PDF::SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        PDF::SetMargins(2, 3 , 2);
        PDF::SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $font = 'helvetica';
        
        $std = $this->clasificaTickets($tickets, "std");
        $off = $this->clasificaTickets($tickets, "off");
        $hojas_off = 0;
        $hojas_std = 0;
        for($i=0; $i<sizeof($std)/8; $i++){
            // add a page
            PDF::AddPage();
            $color = "VERDE";
            $num_hoja = $i+1;
            $title = "<span style='font-size: 40px;'>Hoja ".$color." (".$num_hoja.")</span>";
            PDF::SetFont($font, '', 35);
            PDF::writeHTMLCell($witdh_page, 15, 5, 5, $title, $border=0, $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
            $ticket_por_imprimir = array_slice($std, ($i*8), 8);
            $this->setStar(sizeof($ticket_por_imprimir));
            $this->maquetaTickets($ticket_por_imprimir);
            $hojas_off = $num_hoja;
        }

        for($i=0; $i<sizeof($off)/8; $i++){
            // add a page
            PDF::AddPage();
            $color = "NARANJA";
            $num_hoja = $i+1;
            $title = "<span style='font-size: 40px;'>Hoja ".$color." (".$num_hoja.")</span>";
            PDF::SetFont($font, '', 35);
            PDF::writeHTMLCell($witdh_page, 15, 5, 5, $title, $border=0, $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
            $ticket_por_imprimir = array_slice($off, ($i*8), 8);
            $this->setStar(sizeof($ticket_por_imprimir));
            $this->maquetaTicketsOferta($ticket_por_imprimir);
            $hojas_std = $num_hoja;
        }
        
        $nameFile = time().'.pdf';

        PDF::Output(__DIR__.'/../../../files/'.$nameFile, 'F');
        return response()->json(["archivo" => $nameFile, "off" => $hojas_off, "std"=> $hojas_std]);
    }
     public function maquetaTickets($tickets){
        $witdh_page = 200;
        $height_page = 250;
        $font = 'helvetica';
        $font_size_principal = 3.8;
        $font_size_secondary = 1.9;
        $font_size_aux = 1.2;
        for($i=0; $i<(sizeof($tickets))/2; $i++){
            $html ='<table border="0" style="text-align:center;">
            <tr>';
            $columna='';
            for($j=1; $j<3; $j++){
                $mult = 1;
                $precio = '';
                if((($i*2)-1)+$j<sizeof($tickets)){
                    $contador = 0;
                    foreach($tickets[(($i*2)-1)+$j]['prices'] as $price){
                        $ultimo = sizeof($price);
                        $contador=$contador+1;
                        if(sizeof($price)==2){
                            $mult = 1.2;
                        }
                        if($contador == $ultimo){
                            $precio.= $price['labprint'].'&nbsp;&nbsp;'.$price['price'];
                        }else{
                            $precio.= $price['labprint'].'&nbsp;&nbsp;'.$price['price'].'<br>';
                        }
                    }
                    $columna.= '<th>                    
                    <span style="font-size: '.$font_size_principal.'em;"><b>'.$tickets[(($i*2)-1)+$j]['scode'].'</b><br></span>
                    <span style="font-size: '.$font_size_aux.'em;">'.$tickets[(($i*2)-1)+$j]['item'].'<br></span>
                    <span style="font-size: '.$font_size_secondary*$mult.'em;"><b>'.$precio.'</b></span>
                </th>';
                }else{
                    $columna.='<th></th>';
                }

            }
            $html.= $columna.'</tr>
            </table>';
            PDF::SetFont($font, '', 12);
            //PDF::writeHTMLCell(206, 66.75, 2, 2, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=true);
            PDF::writeHTMLCell($witdh_page, $height_page/4, 5, 22+(($height_page/4)*$i), $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }
     }

     public function maquetaTicketsOferta($tickets){
        $witdh_page = 200;
        $height_page = 250;
        $font = 'helvetica';
        $font_size_principal = 3.8;
        $font_size_secondary = 1.9;
        $font_size_aux = 1.2;
        for($i=0; $i<(sizeof($tickets))/2; $i++){
            $html ='<table border="0" style="text-align:center;">
            <tr>';
            $columna='';
            for($j=1; $j<3; $j++){
                if((($i*2)-1)+$j<sizeof($tickets)){
                    $columna.= '<th>                    
                        <span style="font-size: '.$font_size_principal*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['scode'].'</b><br></span>
                        <span style="font-size: '.$font_size_aux*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['item'].' </b><br></span>
                        <span style="font-size: '.$font_size_aux*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['prices'][0]['labprint'].'<br></b></span>
                        <span style="font-size: '.$font_size_principal*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['prices'][0]['price'].'</b><br></span>
                    </th>';
                }else{
                    $columna.='<th></th>';
                }
            }
            $html.= $columna.'</tr>
            </table>';
            PDF::SetFont($font, '', 12);
            //PDF::writeHTMLCell(206, 66.75, 2, 2, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=true);
            PDF::writeHTMLCell($witdh_page, $height_page/4, 5, 20+(($height_page/4)*$i), $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }
     }
    public function setStar($num){
        $witdh_page = 200;
        $height_page = 250;
        for($i=0; $i<$num; $i++){
            if($i%2==0){
                $a=0;
            }else{
                $a=1;
            }
            switch ($i) {
                case 0:
                case 1:
                    $b =0;
                    break;
                case 2:
                case 3:
                    $b =1;
                    break;
                case 4:
                case 5:
                    $b =2;
                    break;
                case 6:
                case 7:
                    $b =3;
                    break;
            }
            $mask = PDF::Image(__DIR__.'./resources/img/STAR12.png', 0, 0, 0, '', '', '', '', false, 700, '', true);
            //PDF::Image(__DIR__.'./resources/img/STAR12.png', $moveleft, $movetop, $wcll, $hcll, '', '', '', false, 300, '', false, $mask);
            PDF::Image(__DIR__.'./resources/img/STAR12.png', 5+(($witdh_page/2)*$a), 18+(($height_page/4)*$b), $witdh_page/2, $height_page/4, '', '', '', false, 300, '', false, $mask);
        }
    }

    public function clasificaTickets($tickets, $type){
        $tickets_validos = [];
        foreach($tickets as $ticket){
            if($ticket["type"]==$type){
                array_push($tickets_validos, $ticket);
            }
        }
        return $tickets_validos;
    }
}