<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Canje extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'identificador', 'conta', 'id_estacion', 'punto', 'value', 'number_usuario', 'generado', 'estado', 'descrip', 'image', 'estado_uno', 'estado_dos', 'estado_tres', 'created_at', 'updated_at'];
}
