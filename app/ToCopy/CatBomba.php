<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatBomba extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'nombre', 'numero', 'id_estacion', 'id_empresa', 'activo', 'created_at', 'updated_at'];
}
