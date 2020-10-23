<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DispatcherHistoryPayment extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
    /* Accediendo a la tabla ventas */
    protected $table = 'sales';
    // Enlace con el tipo de gasolina
    public function gasoline()
    {
        return $this->belongsTo(Gasoline::class);
    }
    // Enlace con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
