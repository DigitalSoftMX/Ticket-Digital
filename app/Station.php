<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $connection = 'mysql';
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
