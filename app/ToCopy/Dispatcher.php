<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $table = 'dispatcher';
    protected $fillable = ['id', 'qr_dispatcher', 'active', 'todate', 'id_users', 'id_station'];
}
