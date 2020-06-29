<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SharedBalance extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
}
