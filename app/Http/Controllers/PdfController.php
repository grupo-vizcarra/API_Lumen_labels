<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PDF;

class PdfController extends Controller{
    public function createPdf(Request $request){
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
        $my = $this->clasificaTickets($tickets, "my");
        foreach($my as $item){
            array_push($std, $item);
        }
        $off = $this->clasificaTickets($tickets, "off");
        $hojas_off = 0;
        $hojas_std = 0;
        for($i=0; $i<sizeof($std)/15; $i++){
            // add a page
            PDF::AddPage();
            $color = "VERDE";
            $num_hoja = $i+1;
            $title = "<span style='font-size: 40px;'>Hoja ".$color." (".$num_hoja.")</span>";
            PDF::SetFont($font, '', 35);
            PDF::writeHTMLCell($witdh_page, 15, 5, 5, $title, $border=0, $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
            $ticket_por_imprimir = array_slice($std, ($i*15), 15);
            $this->setStar(sizeof($ticket_por_imprimir));
            $this->maquetaTickets($ticket_por_imprimir, $isPack);
            $hojas_std = $num_hoja;
        }
        for($i=0; $i<sizeof($off)/15; $i++){
            // add a page
            PDF::AddPage();
            $color = "NARANJA";
            $num_hoja = $i+1;
            $title = "<span style='font-size: 40px;'>Hoja ".$color." (".$num_hoja.")</span>";
            PDF::SetFont($font, '', 35);
            PDF::writeHTMLCell($witdh_page, 15, 5, 5, $title, $border=0, $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
            $ticket_por_imprimir = array_slice($off, ($i*15), 15);
            $this->setStar(sizeof($ticket_por_imprimir));
            $this->maquetaTicketsOferta($ticket_por_imprimir, $isPack);
            $hojas_off = $num_hoja;
        }
        $nameFile = time().'.pdf';

        PDF::Output(__DIR__.'/../../../files/'.$nameFile, 'F');
        return response()->json(["archivo" => $nameFile, "off" => $hojas_off, "std"=> $hojas_std, "dasda" => sizeof($std)]);
    }

    public function pdfBodega($ticket, $isPack){
        $witdh_page = 200;
        $height_page = 250;
        $font = 'helvetica';

        for($i=0; $i<sizeof($ticket)/16; $i++){
            // add a page
            PDF::AddPage();
            $color = "Página ";
            $num_hoja = $i+1;
            $title = "<span style='font-size: 40px;'>".$color." (".$num_hoja.")</span>";
            PDF::SetFont($font, '', 35);
            PDF::writeHTMLCell($witdh_page, 15, 5, 5, $title, $border=0, $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
            $ticket_por_imprimir = array_slice($ticket, ($i*16), 16);
            $this->maquetaTickets_bodega($ticket_por_imprimir, $isPack);
            $hojas = $num_hoja;
        }

        $nameFile = time().'.pdf';

        PDF::Output(__DIR__.'/../../../files/'.$nameFile, 'F');
        return response()->json(["archivo" => $nameFile, "hojas" => $hojas]);
    }

    public function drawBottomLine($cantidad){
        $witdh_page = 200;
        $height_page = 250;
        $linea_horizontal = '<span style="border:solid black 5px; height: '.$height_page.'; widtth:'.$witdh_page.';"></span>';
        PDF::writeHTMLCell($witdh_page/2, (($height_page/8)*$cantidad)+15, 5, "", $linea_horizontal, $border='R', $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
        for($i=0; $i<$cantidad; $i++){
            PDF::writeHTMLCell($witdh_page, $height_page/8, 5, 21+(($height_page/8)*$i), $linea_horizontal, $border='B', $ln=0, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }
    }
    public function maquetaTickets_bodega($tickets, $isPack){
        $font = 'helvetica';
        $font_size_principal = 3.2;
        $font_size_secondary = 1.8;
        $font_size_aux = 1.8;
        $witdh_page = 200;
        $height_page = 250;
        $this->drawBottomLine(sizeof($tickets)/2);
        for($i=0; $i<(sizeof($tickets)/2); $i++){
            $html ='<table border="0" style="text-align:center;">
            <tr>';
            $columna='';
            for($j=1; $j<3; $j++){
                if((($i*2)-1)+$j<sizeof($tickets)){
                    $pz = ($isPack ? ' '.$tickets[(($i*2)-1)+$j]['ipack'].'pz':'');
                    if(strlen($tickets[(($i*2)-1)+$j]['item'])>10){
                        $font_size_principal = 2.6;
                    }else if(strlen($tickets[(($i*2)-1)+$j]['item'])>8){
                        $font_size_principal = 2.8;
                    }
                    $columna.= '<th>                    
                        <span style="font-size: '.$font_size_principal*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['item'].' </b></span><br>
                        <span style="font-size: '.$font_size_aux*1.1.'em;"><b>'.$tickets[(($i*2)-1)+$j]['scode'].$pz.'</b></span><br>
                    </th>';
                }else {
                    $columna.='<th></th>';
                }
            }
            $html.= $columna.'</tr>
        </table>';
        PDF::SetFont($font, '', 12);
        PDF::writeHTMLCell($witdh_page, $height_page/8, 5, 21+(($height_page/8)*$i), $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }

    }
     public function maquetaTickets($tickets, $isPack){
        $witdh_page = 200;
        $height_page = 250;
        $font = 'helvetica';
        $font_size_principal = 2;
        $font_size_secondary = 1.5;
        $font_size_aux = 1.1;
        for($i=0; $i<(sizeof($tickets))/3; $i++){
            $html ='<table border="0" style="text-align:center;">
            <tr>';
            $columna='';
            for($j=1; $j<4; $j++){
                $mult = 1;
                $precio = '';
                if((($i*2)+$i-1)+$j<sizeof($tickets)){
                    $pz = ($isPack ? ' '.$tickets[(($i*2)+$i-1)+$j]['ipack'].'pz':'');
                    $tool = $tickets[(($i*2)+$i-1)+$j]['tool'];
                    if(strlen($tool)==1){
                        $font_size_principal = 2.1;
                        $font_size_secondary = 1.5;
                        $tool = '-'.$tickets[(($i*2)+$i-1)+$j]['tool'];
                    }else if(strlen($tool)==2){
                        $font_size_principal = 1.85;
                        $font_size_secondary = 1.45;
                        $tool = '-'.$tickets[(($i*2)+$i-1)+$j]['tool'];
                    }else{
                        $tool = '';
                        $font_size_principal = 2.3;
                    }
                    $contador = 0;
                    $labprice = '';
                    $ayuda = '';
                    if(sizeof($tickets[(($i*2)+$i-1)+$j]['prices'])==1){
                        $ayuda.= '<span style="font-size: '.$font_size_aux.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['labprint'].'<br></b></span>
                                <span style="font-size: 3.2em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['price'].'</b></span>';
                    }else if(sizeof($tickets[(($i*2)+$i-1)+$j]['prices'])==2){
                        $mult = 1.3;
                        $labprice = '<span style="font-size: '.$font_size_secondary*$mult.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['labprint'].'&nbsp;&nbsp;'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['price'].'<br>'.$tickets[(($i*2)+$i-1)+$j]['prices'][1]['labprint'].'&nbsp;&nbsp;'.$tickets[(($i*2)+$i-1)+$j]['prices'][1]['price'].'</b></span>';
                    }else{
                        foreach($tickets[(($i*2)+$i-1)+$j]['prices'] as $price){
                            $ultimo = sizeof($price);
                            $contador=$contador+1;
                            if($contador == $ultimo){
                                $precio.= $price['labprint'].'&nbsp;&nbsp;'.$price['price'];
                                $labprice = '<span style="font-size: '.$font_size_secondary*$mult.'em;"><b>'.$precio.'</b></span>';
                            }else{
                                $precio.= $price['labprint'].'&nbsp;&nbsp;'.$price['price'].'<br>';
                            }
                        }
                    }
                    $columna.= '<th>                    
                    <span style="font-size: '.$font_size_principal.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['scode'].$tool.'</b><br></span>
                    <span style="font-size: '.$font_size_aux.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['item'].$pz.'</b><br></span>
                    '.$labprice.$ayuda.'
                </th>';
                }else{
                    $columna.='<th></th>';
                }
            }
            $html.= $columna.'</tr>
            </table>';
            PDF::SetFont($font, '', 12);
            //PDF::writeHTMLCell(206, 66.75, 2, 2, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=true);
            PDF::writeHTMLCell($witdh_page, $height_page/5, 5, 21+(($height_page/5)*$i), $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }
     }

     public function maquetaTicketsOferta($tickets, $isPack){
        $witdh_page = 200;
        $height_page = 250;
        $font = 'helvetica';
        $font_size_principal = 3.6;
        $font_size_secondary = 1.9;
        $font_size_aux = 1.1;
        for($i=0; $i<(sizeof($tickets))/3; $i++){
            $html ='<table border="0" style="text-align:center;">
            <tr>';
            $columna='';
            for($j=1; $j<4; $j++){
                if((($i*2)+$i-1)+$j<sizeof($tickets)){
                    $pz = ($isPack ? ' '.$tickets[(($i*2)+$i-1)+$j]['ipack'].'pz':'');
                    $tool = $tickets[(($i*2)+$i-1)+$j]['tool'];
                    if(strlen($tool)==1){
                        $font_size_principal = 2.1;
                        $font_size_secondary = 1.5;
                        $tool = '-'.$tickets[(($i*2)+$i-1)+$j]['tool'];
                    }else if(strlen($tool)==2){
                        $font_size_principal = 1.85;
                        $font_size_secondary = 1.45;
                        $tool = '-'.$tickets[(($i*2)+$i-1)+$j]['tool'];
                    }else{
                        $tool = '';
                        $font_size_principal = 2.3;
                    }
                    $columna.= '<th>                    
                        <span style="font-size: '.$font_size_principal*1.1.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['scode'].$tool.'</b><br></span>
                        <span style="font-size: '.$font_size_aux*1.1.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['item'].$pz.'<br></b></span>
                        <span style="font-size: '.$font_size_aux*1.1.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['labprint'].'<br></b></span>
                        <span style="font-size: '.$font_size_principal*1.5.'em;"><b>'.$tickets[(($i*2)+$i-1)+$j]['prices'][0]['price'].'</b></span>
                    </th>';
                }else{
                    $columna.='<th></th>';
                }
            }
            $html.= $columna.'</tr>
            </table>';
            PDF::SetFont($font, '', 12);
            //PDF::writeHTMLCell(206, 66.75, 2, 2, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=true);
            PDF::writeHTMLCell($witdh_page, $height_page/5, 4, 21+(($height_page/5)*$i), $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='center', $autopadding=false);
        }
     }
    public function setStar($num){
        $witdh_page = 200;
        $height_page = 250;
        for($i=0; $i<$num; $i++){
            if($i%3==0){
                $a=0;
            }else if($i==1 || $i==4 || $i==7 || $i==10 || $i==13){
                $a=1;
            }else{
                $a=2;
            }
            switch ($i) {
                case 0:
                case 1:
                case 2:
                    $b =0;
                    break;
                case 3:
                case 4:
                case 5:
                    $b =1;
                    break;
                case 6:
                case 7:
                case 8:
                    $b =2;
                    break;
                case 9:
                case 10:
                case 11:
                    $b =3;
                    break;
                case 12:
                case 13:
                case 14:
                    $b =4;
                    break;
            }
            $mask = PDF::Image(__DIR__.'./resources/img/STAR12.png', 0, 0, 0, '', '', '', '', false, 700, '', true);
            //PDF::Image(__DIR__.'./resources/img/STAR12.png', $moveleft, $movetop, $wcll, $hcll, '', '', '', false, 300, '', false, $mask);
            PDF::Image(__DIR__.'./resources/img/STAR12.png', 5+(($witdh_page/3)*$a), 17+(($height_page/5)*$b), $witdh_page/3, $height_page/5, '', '', '', false, 300, '', false, $mask);
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