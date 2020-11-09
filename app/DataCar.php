<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataCar extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
    protected $fillable = ['client_id', 'number_plate', 'type_car'];
}
