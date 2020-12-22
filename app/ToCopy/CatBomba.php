<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatBomba extends Model
{
    protected $fillable = ['id', 'nombre', 'numero', 'id_estacion', 'id_empresa', 'activo', 'created_at', 'updated_at'];
}
