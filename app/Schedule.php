<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    // Accediendo a la base de datos por default del proyecto
    protected $connection = 'mysql';
}