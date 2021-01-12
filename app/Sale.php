<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['dispatcher_id', 'sale', 'gasoline_id', 'liters', 'payment', 'schedule_id', 'station_id', 'client_id', 'time_id', 'no_island', 'no_bomb', 'transmitter_id'];
    // Enlace con el tipo de gasolina
    public function gasoline()
    {
        return $this->belongsTo(Gasoline::class);
    }
    // Enlace con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
