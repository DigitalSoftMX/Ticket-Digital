<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';

    public function users(){
        return $this->belongsToMany('App\User','Role')->withPivot('id','name');
    }

    protected $guarded = ['id'];
}
