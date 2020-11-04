<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatType extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    /* Accediendo a la tabla station */
    protected $table = 'cat_type';
    protected $fillable = ['id', 'name_type'];
}
