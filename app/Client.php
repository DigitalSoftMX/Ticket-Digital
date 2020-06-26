<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /* Accediendo a la base de datos por default del proyecto */
    protected $connection = 'mysql';
    
    /* public function user(){
        return $this->hasOne(User::class);
    } */

    public function historyDeposits(){
        return $this->belongsTo(UserHistoryDeposit::class);
    }

    public function contacts(){
        return $this->belongsTo(Contact::class);
    }

    protected $hidden = [
        'user_id','created_at', 'updated_at',
    ];
}
