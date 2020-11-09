<?php

namespace App;

use App\Web\Island;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $connection = 'mysql';
    /* Accediendo a la tabla station */
    protected $table = 'station';

    protected $fillable = ['ip'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    // Relacion con los horarios de la estacion
    public function schedules()
    {
        return $this->belongsTo(Schedule::class);
    }
    // Relacino con las islas y bombas de la estacion
    public function islands()
    {
        return $this->hasMany(Island::class);
    }
}
