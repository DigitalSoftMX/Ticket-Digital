<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    // Funcion enlace con la tabla clients para obtener el contacto del usuario
    public function receiver()
    {
        return $this->belongsTo(Client::class);
    }
}
