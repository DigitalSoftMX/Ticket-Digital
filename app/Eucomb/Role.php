<?php

namespace App\Eucomb;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'eucomb';
    // Relacion con los usuarios
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
