<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CountVoucher extends Model
{
    protected $fillable = ['id', 'id_station', 'min', 'max'];
}
