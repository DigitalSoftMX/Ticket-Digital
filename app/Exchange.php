<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $fillable = ['client_id', 'exchange', 'station_id', 'points', 'value', 'status', 'admin_id'];
}
