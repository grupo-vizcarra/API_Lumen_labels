<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Printers extends Model{
    protected $table = 'printers';
    protected $primaryKey = 'id_printer';
    protected $fillable = ['print_name', 'print_main'];
}