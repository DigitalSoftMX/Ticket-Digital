<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['user_id', 'current_balance', 'shared_balance', 'points', 'image', 'birthdate', 'ids'];
    // Relacion con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relacion para los depositos realizados por el cliente
    public function historyDeposits()
    {
        return $this->belongsTo(UserHistoryDeposit::class);
    }
    // Relacion para los contactos del cliente
    public function contacts()
    {
        return $this->belongsTo(Contact::class);
    }
    // Relacion con el tipo de vehiculo
    public function car()
    {
        return $this->hasOne(DataCar::class);
    }
    // Relacion con los pagos que ha realizado
    public function payments()
    {
        return $this->belongsTo(DispatcherHistoryPayment::class);
    }
}
