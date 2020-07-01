<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserHistoryDeposit extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
    // Funcion para obtener la estacion a la que pertenece el abono
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    protected $hidden = [
        'station_id'
    ];
}
