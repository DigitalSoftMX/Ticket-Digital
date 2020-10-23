<?php

namespace App;

use App\Api\Status;
use Illuminate\Database\Eloquent\Model;

class UserHistoryDeposit extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
    /* Accediendo a la tabla deposit */
    protected $table = 'deposits';
    // Conexion con la estacion a la que pertenece el abono
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Conexion con la estacion a la que pertenece el abono
    public function deposit()
    {
        return $this->belongsTo(Status::class, 'status', 'id');
    }
}
