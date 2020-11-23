<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataCar extends Model
{
    protected $fillable = ['client_id', 'number_plate', 'type_car'];
}
