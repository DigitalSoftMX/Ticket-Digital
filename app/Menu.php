<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';

    public function roles(){
        return $this->belongsToMany('App\Role');
    }
}
