<?php

namespace App;

use App\Api\Status;
use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $fillable = ['client_id', 'exchange', 'station_id', 'points', 'value', 'status', 'admin_id'];
    // Relacion con las estaciones
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Relacion con el status
    public function estado()
    {
        return $this->belongsTo(Status::class, 'status', 'id');
    }
}
