<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    public function users(){
        return $this->belongsTo('App\User');
    }

    public function stations(){
        return $this->belongsTo('App\Station');
    }
}
