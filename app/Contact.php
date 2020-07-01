<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
    // Funcion enlace con la tabla clients para obtener el contacto del usuario
    public function receiver()
    {
        return $this->belongsTo(Client::class);
    }
}
