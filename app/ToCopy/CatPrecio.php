<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatPrecio extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'num_ticket','costo','costo_timbre','costo_admin','costo_timbre_admin','ganancia','created_at','updated_at'];
}
