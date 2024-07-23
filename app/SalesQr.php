<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesQr extends Model
{
    protected $fillable = ['sale', 'gasoline_id', 'liters', 'points', 'payment', 'station_id', 'client_id', 'no_bomb', 'main_id', 'reference', 'reference_code'];
    // Relacion con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Relacion con el cliente
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
