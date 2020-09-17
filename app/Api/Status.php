<?php

namespace App\Api;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    // Accediendo a la base de datos de Ticket Digital
    protected $connection = 'mysql';
    // Accediendo a la tabla status
    protected $table = 'status';
}
