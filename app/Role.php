<?php

namespace App;

use App\ToCopy\Permission;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';

    protected $fillable = ['id', 'name', 'description', 'display_name', 'created_at', 'updated_at', 'deleted_at'];

    public function users()
    {
        return $this->belongsToMany('App\User', 'Role')->withPivot('id', 'name');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    protected $guarded = ['id'];
}
