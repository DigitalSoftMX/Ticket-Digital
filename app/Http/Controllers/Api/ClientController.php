<?php

namespace App\Http\Controllers\Api;

use App\DispatcherHistoryPayment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function main()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (($car = $user->client->car) != "") {
                $dataCar = array('number_plate' => $car->number_plate, 'type_car' => $car->type_car);
            } else {
                $dataCar = array('number_plate' => '', 'type_car' => '');
            }
            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['email'] = $user->email;
            $data['client']['membership'] = $user->client->membership;
            $data['client']['current_balance'] = $user->client->current_balance;
            $data['client']['shared_balance'] = $user->client->shared_balance;
            $data['client']['total_shared_balance'] = count(SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0], ['status', 4]])->get());
            $data['client']['points'] = $user->client->points;
            $data['client']['image_qr'] = $user->client->image_qr;
            $data['data_car'] = $dataCar;
            return $this->successResponse('user', $data);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion principal para la ventana de abonos
    public function getListStations()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $data = array();
            foreach (Station::all() as $station) {
                array_push($data, array('id' => $station->id, 'name' => $station->name));
            }
            return $this->successResponse('stations', $data);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            try {
                switch ($request->type) {
                    case 'payment':
                        if (count($balances = $this->getBalances(new DispatcherHistoryPayment(), $request->start, $request->end, $user, null, null)) > 0) {
                            $payments = array();
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->payment;
                                $data['station'] = $balance->station->name;
                                $data['liters'] = $balance->liters;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                $data['gasoline'] = $balance->gasoline->name;
                                $data['no_island'] = $balance->no_island;
                                $data['no_bomb'] = $balance->no_bomb;
                                array_push($payments, $data);
                            }
                            return $this->successResponse('payments', $payments);
                        }
                    case 'balance':
                        if (count($balances = $this->getBalances(new UserHistoryDeposit(), $request->start, $request->end, $user, 4, null)) > 0) {
                            $payments = array();
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->balance;
                                $data['station'] = $balance->station->name;
                                $data['status'] = $balance->deposit->name;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                array_push($payments, $data);
                            }
                            return $this->successResponse('balances', $payments);
                        }
                    case 'share':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'transmitter_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'receiver');
                            return $this->successResponse('balances', $payments);
                        }
                    case 'received':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'receiver_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'transmitter');
                            return $this->successResponse('balances', $payments);
                        }
                }
                return $this->errorResponse('Sin movimientos en la cuenta');
            } catch (Exception $e) {
                return $this->errorResponse('Error de consulta por fecha');
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver el arreglo de historiales
    private function getBalances($model, $start, $end, $user, $status, $type)
    {
        $query = [['client_id', $user->client->id]];
        if ($type != null) {
            $query = [[$type, $user->client->id]];
        }
        if ($status != null) {
            $query[1] = ['status', '!=', $status];
        }
        if ($start == "" && $end == "") {
            $balances = $model::where($query)->get();
        } elseif ($start == "") {
            $balances = $model::where($query)->whereDate('created_at', '<=', $end)->get();
        } elseif ($end == "") {
            $balances = $model::where($query)->whereDate('created_at', '>=', $start)->get();
        } else {
            if ($start > $end) {
                return null;
            } else {
                $balances = $model::where($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();
            }
        }
        return $balances;
    }
    // Obteniendo el historial enviodo o recibido
    private function getSharedBalances($balances, $person)
    {
        $payments = array();
        foreach ($balances as $balance) {
            $payment['station'] = $balance->station->name;
            $payment['balance'] = $balance->balance;
            $payment['membership'] = $balance->$person->membership;
            $payment['name'] = $balance->$person->user->name . ' ' . $balance->$person->user->first_surname . ' ' . $balance->$person->user->second_surname;
            $payment['date'] = $balance->created_at->format('Y/m/d');
            array_push($payments, $payment);
        }
        return $payments;
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->errorResponse('Token invalido');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    // Funcion mensajes de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje correcto
    private function successResponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
}
