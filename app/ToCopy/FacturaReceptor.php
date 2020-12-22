<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class FacturaReceptor extends Model
{
    protected $table = 'factura_receptor';
    protected $fillable = ['id', 'nombre', 'rfc', 'usocfdi', 'emailfiscal', 'id_user', 'created_at', 'updated_at'];
}
