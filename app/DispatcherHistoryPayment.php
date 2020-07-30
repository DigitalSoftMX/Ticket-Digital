<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DispatcherHistoryPayment extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
    // Enlace con el tipo de gasolina
    public function gasoline()
    {
        return $this->belongsTo(Gasoline::class);
    }
}
