<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['transmitter_id', 'receiver_id'];
    // Funcion enlace con la tabla clients para obtener el contacto del usuario
    public function receiver()
    {
        return $this->belongsTo(Client::class);
    }
}
