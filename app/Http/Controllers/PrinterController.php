<?php

namespace App\Http\Controllers;
use App\Printers;
use Illuminate\Http\Request;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;

use Laravel\Lumen\Routing\Controller as BaseController;

class PrinterController extends BaseController{
    public function getAll(){
        $rows = Printers::all();
        return response()->json($rows);
    }
    public function printTickets(Request $request){
        $impresora = $request->printer;
        $host= gethostname();
        $ipserver = gethostbyname($host);
        $connector = new WindowsPrintConnector("smb://".$ipserver."/".$impresora);
        $printer = new Printer($connector);
        $printer -> setJustification(Printer::JUSTIFY_CENTER);
        $printer -> selectPrintMode(Printer::MODE_FONT_B);
        $printer->setEmphasis(true);

        $tickets = $request->tickets;

        foreach($tickets as $ticket){
            $printer->setTextSize(8,8);
            if($request->isPrice){
                $printer->text($ticket["scode"]);
            }else{
                $printer->text($ticket["item"]);    
            }
            if($request->isPack){
                $printer->setTextSize(4,2);
                $printer->text("\r\n\r\n".$ticket["ipack"]." Pz");                
            }
            $printer->setTextSize(2,2);
            $printer->setTextSize(1,1);
            $printer->text("\r\n-----------------------------------------------------\r\n");
            $printer->setTextSize(4,2);
            if($request->isPrice){
                $printer->text($ticket["item"]);    
            }else{
                $printer->text($ticket["scode"]);
            }
            $printer->setTextSize(1,1);
            $printer->text("\r\n---------------------------------------------------------------\r\n");
            $printer->setTextSize(3,2);
            if($request->isPrice){
                foreach($ticket["prices"] as $price){
                    if($ticket["type"]=="my" || $ticket["type"]=="off"){
                        $printer->text($price["labprint"]."\r\n\r\n");                    
                        $printer->setTextSize(8,8);
                        $printer->text($price["price"]);
                    }else{
                        $printer->text($price["labprint"]." ".$price["price"]."\r\n\r\n");
                    }
                }
            }
            //$printer->qrCode("1234",Printer::QR_ECLEVEL_L,6,Printer::QR_MODEL_1);
            //$printer->setTextSize(1,1);
            //$printer->text("Grupo Vizcarra");
            $printer->feed(2);
            $printer->cut();
            $printer->close();
        }
        return response("Ok", 200);
    }
}