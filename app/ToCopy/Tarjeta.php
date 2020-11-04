<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Tarjeta extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tarjeta';
    protected $fillable = ['id', 'number_usuario', 'active', 'todate', 'totals', 'visits', 'id_users', 'created_at', 'updated_at'];
}
