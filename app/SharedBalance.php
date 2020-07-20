<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SharedBalance extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
    // Funcion para obtener la informacion de la estacion por medio de su id
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Funcion para obtener la informacion del emisor de saldo
    public function transmitter()
    {
        return $this->belongsTo(Client::class);
    }
    // Funcion para obtener la informacion del receptor de saldo
    public function receiver()
    {
        return $this->belongsTo(Client::class);
    }
}
