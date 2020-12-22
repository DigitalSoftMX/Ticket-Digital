<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{
    protected $table = 'dispatcher';
    protected $fillable = ['id', 'qr_dispatcher', 'active', 'todate', 'id_users', 'id_station'];
}
