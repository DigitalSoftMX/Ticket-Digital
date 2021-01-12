<?php

namespace App;

use App\Web\Island;
use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{
    protected $fillable = ['id', 'user_id', 'station_id', 'created_at', 'updated_at'];
    // Relacion con la tabla de usuarios
    public function users()
    {
        return $this->belongsTo(User::class);
    }
    // relacion con las islas
    public function island()
    {
        return $this->belongsTo(Island::class);
    }
    // Relacion con las estaciones 
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
    // Relacion con la tabla de registro de cobros
    public function historyPayments()
    {
        return $this->belongsTo(Sale::class);
    }
    // Relacion con los turnos
    public function times()
    {
        return $this->hasMany(RegisterTime::class);
    }
}
