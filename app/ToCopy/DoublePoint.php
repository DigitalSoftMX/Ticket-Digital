<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class DoublePoint extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $table='doublepoint';
    protected $fillable = ['id', 'active','created_at','updated_at'];
}
