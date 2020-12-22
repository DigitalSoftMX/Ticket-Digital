<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class CatType extends Model
{
    // Accedeciendo a la tabla cat_type
    protected $table = 'cat_type';
    protected $fillable = ['id', 'name_type'];
}
