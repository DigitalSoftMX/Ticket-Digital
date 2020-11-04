<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Key extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'publickey', 'privatekey', 'created_at', 'updated_at'];
}
