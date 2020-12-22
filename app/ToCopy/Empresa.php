<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['id', 'nombre', 'direccion', 'telefono', 'imglogo', 'total_facturas', 'total_timbres', 'activo', 'id_user', 'created_at', 'updated_at'];
}
