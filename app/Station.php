<?php

namespace App;

use App\Web\Island;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    /* Accediendo a la tabla station */
    protected $table = 'station';

    protected $fillable = ['ip', 'fail'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // Relacion con los horarios de la estacion
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
    // Relacino con las islas y bombas de la estacion
    public function islands()
    {
        return $this->hasMany(Island::class);
    }
    // Relacion con los vales de la estacion
    public function voucher()
    {
        return $this->hasOne(Voucher::class, 'id_station', 'id');
    }
    // Relacion con el rango de vales de la estacion
    public function vouchers()
    {
        return $this->hasMany(CountVoucher::class, 'id_station', 'id');
    }
    // Relacion con los vales
    public function exchanges()
    {
        return $this->hasMany(Exchange::class);
    }
}
