<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacion para los depositos realizados por el cliente
    public function historyDeposits()
    {
        return $this->belongsTo(UserHistoryDeposit::class);
    }
    // Relacion para los contactos del cliente
    public function contacts()
    {
        return $this->belongsTo(Contact::class);
    }
    // Relacion con el tipo de vehiculo
    public function car()
    {
        return $this->hasOne(DataCar::class);
    }
}
