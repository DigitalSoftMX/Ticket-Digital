<?php

namespace App\Eucomb;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'eucomb';
}
