<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = ['id', 'name', 'points', 'value', 'img', 'id_status', 'id_station', 'active', 'created_at', 'updated_at'];
}
