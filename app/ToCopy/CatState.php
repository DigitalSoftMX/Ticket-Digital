<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatState extends Model
{
    /* Accediendo a la tabla cat_state */
    protected $table = 'cat_state';
    protected $fillable = ['id', 'name_state'];
}
