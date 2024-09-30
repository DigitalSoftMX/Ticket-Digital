<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferredDispatcherClient extends Model
{
    protected $fillable = ['user_id', 'client_id'];
}
