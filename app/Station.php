<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $connection = 'mysql';
    /* Accediendo a la tabla station */
    protected $table = 'station';
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
}
