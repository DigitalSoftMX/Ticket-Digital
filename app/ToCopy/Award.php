<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'name', 'points', 'value', 'img', 'id_status', 'id_station','active', 'created_at', 'updated_at'];
}
