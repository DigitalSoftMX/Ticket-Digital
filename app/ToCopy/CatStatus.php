<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatStatus extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $table='cat_status';
    protected $fillable = ['id', 'name_status'];
}
