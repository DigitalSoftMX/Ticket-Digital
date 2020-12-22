<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class ConjuntoMembership extends Model
{
    protected $fillable = ['id', 'membresia', 'number_usuario', 'puntos', 'created_at', 'updated_at'];
}
