<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $fillable = ['id', 'pago', 'num_timbres', 'archivo', 'autorizado', 'id_estacion', 'id_empresa', 'created_at', 'updated_at'];
}
