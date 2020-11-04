<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class ChangeMembership extends Model
{
    /* Accediendo a la base de datos de Eucomb */
    protected $connection = 'mysql';
    protected $fillable = ['id', 'qr_membership', 'id_users', 'qr_membership_old', 'todate'];
}
