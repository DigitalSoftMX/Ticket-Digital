<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatState extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    /* Accediendo a la tabla station */
    protected $table = 'cat_state';
    protected $fillable = ['id', 'name_state'];
}
