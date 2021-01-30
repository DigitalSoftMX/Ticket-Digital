<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CountVoucher extends Model
{
    protected $table = 'ranges';
    protected $fillable = ['remaining'];
}
