<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class DoublePoint extends Model
{
    protected $table='doublepoint';
    protected $fillable = ['id', 'active','created_at','updated_at'];
}
