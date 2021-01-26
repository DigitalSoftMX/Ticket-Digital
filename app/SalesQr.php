<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesQr extends Model
{
    protected $fillable = ['sale', 'gasoline_id', 'liters', 'points', 'payment', 'station_id', 'client_id', 'no_bomb'];
}
