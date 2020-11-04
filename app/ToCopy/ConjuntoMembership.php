<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class ConjuntoMembership extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'membresia', 'number_usuario', 'puntos', 'created_at', 'updated_at'];
}
