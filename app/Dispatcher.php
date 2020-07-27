<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
    // Relacion con la tabla de usuarios
    public function users()
    {
        return $this->belongsTo(User::class);
    }
    // Relacion con las estaciones 
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Relacion con la tabla de registro de cobros
    public function historyPayments()
    {
        return $this->belongsTo(DispatcherHistoryPayment::class);
    }
}
