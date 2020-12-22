<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class UserEstacion extends Model
{
    protected $table = 'users_estaciones';
    protected $fillable = ['id', 'id_users', 'id_station', 'created_at', 'updated_at'];
}
