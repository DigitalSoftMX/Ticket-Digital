<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatStatus extends Model
{
    protected $table='cat_status';
    protected $fillable = ['id', 'name_status'];
}
