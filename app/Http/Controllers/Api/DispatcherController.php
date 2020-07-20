<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\User;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function main()
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $user = Auth::user();
            $data = array(
                'id' => $user->id,
                'name' => $user->name,
                'first_surname' => $user->first_surname,
                'second_surname' => $user->second_surname,
                'email' => $user->email,
                'sex' => $user->sex,
                'phone' => $user->phone,
                'dispatcher_id' => $user->dispatcher->dispatcher_id,
            );
            return $this->successMessage('user', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makePayment(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $dispatcher = Auth::user()->dispatcher;
            if ($dispatcher->station_id == $request->id_station) {
                $client = Client::where('membership', $request->membership)->first();
                if ($request->tr_membership == "") {
                    $payment = UserHistoryDeposit::where([['client_id', $client->id], ['station_id', $request->id_station]])->first();
                    if ($payment != null) {
                        if ($request->price <= $payment->balance) {
                            $payment->balance -= $request->price;
                            $payment->save();
                            // Falta guardar historial de pago
                            $client->current_balance -= $request->price;
                            $client->save();
                            return $this->successMessage('payment', 'Cobro realizado correctamente');
                        } else {
                            return $this->errorMessage('No hay saldo suficiente');
                        }
                    } else {
                        return $this->errorMessage('No hay abonos realizados');
                    }
                } else {
                    $transmitter = Client::where('membership', $request->tr_membership)->first();
                    $payment = SharedBalance::where([['transmitter_id', $transmitter->id], ['receiver_id', $client->id], ['station_id', $request->id_station]])->first();
                    if ($payment != null) {
                        if ($request->price <= $payment->balance) {
                            $payment->balance -= $request->price;
                            $payment->save();
                            // Falta guardar historial de pago
                            $client->shared_balance -= $request->price;
                            $client->save();
                            return $this->successMessage('payment', 'Cobro realizado correctamente');
                        } else {
                            return $this->errorMessage('No hay saldo suficiente');
                        }
                    } else {
                        return $this->errorMessage('No hay abonos realizados');
                    }
                }
            } else {
                return $this->errorMessage('Estacion incorrecta');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion mensajes de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje correcto
    private function successMessage($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Funcion para guardar historial de abonos a la cuenta del cliente
    private function saveHistoryBalanceClient($history, $balance)
    {
        $historyBalance = new History();
        if ($balance != 0) {
            $history->balance = $balance;
        }
        $historyBalance->client_id = $history->client_id;
        $historyBalance->action = $history;
        $historyBalance->type = 'payment';
        $historyBalance->save();
    }
}
