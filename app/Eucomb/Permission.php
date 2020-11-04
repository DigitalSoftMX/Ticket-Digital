<?php

namespace App\Eucomb;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'eucomb';
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
