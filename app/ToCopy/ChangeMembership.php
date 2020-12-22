<?php

namespace App\ToCopy;

use Illuminate\Database\Eloquent\Model;

class ChangeMembership extends Model
{
    protected $fillable = ['id', 'qr_membership', 'id_users', 'qr_membership_old', 'todate'];
}
