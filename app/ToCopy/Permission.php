<?php

namespace App\ToCopy;

use App\Role;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'name', 'display_name', 'description', 'created_at', 'updated_at', 'deleted_at'];
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
