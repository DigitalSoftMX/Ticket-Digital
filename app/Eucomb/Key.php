<?php

namespace App\Eucomb;

use Illuminate\Database\Eloquent\Model;

class Key extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'eucomb';
}
