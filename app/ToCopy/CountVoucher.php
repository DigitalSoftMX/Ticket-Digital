<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CountVoucher extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'id_station', 'min', 'max'];
}
