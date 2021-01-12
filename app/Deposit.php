<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Api\Status;

class Deposit extends Model
{
    protected $fillable = ['client_id', 'balance', 'image_payment', 'station_id', 'status'];
    // Conexion con la estacion a la que pertenece el abono
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Conexion con la estacion a la que pertenece el abono
    public function deposit()
    {
        return $this->belongsTo(Status::class, 'status', 'id');
    }
}
