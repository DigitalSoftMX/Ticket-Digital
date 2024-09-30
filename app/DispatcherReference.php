<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DispatcherReference extends Model
{
    protected $fillable = ['referrer_code', 'user_id', 'station_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
