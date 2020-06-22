<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
    
    public function users(){
        return $this->belongsTo('App\User');
    }

    public function stations(){
        return $this->belongsTo('App\Station');
    }
}
