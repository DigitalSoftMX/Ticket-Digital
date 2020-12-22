<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = ['id', 'name', 'points', 'value', 'id_status', 'id_station', 'id_count_voucher', 'total_voucher'];
}
