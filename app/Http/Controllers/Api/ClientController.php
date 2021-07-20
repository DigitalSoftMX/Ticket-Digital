<?php

namespace App\Http\Controllers\Api;

use App\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\Deposit;
use App\Exchange;
use App\SalesQr;
use App\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function index()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['email'] = $user->email;
            $data['client']['membership'] = $user->username;
            $data['client']['current_balance'] = $user->client->deposits->where('status', 4)->sum('balance');
            $data['client']['shared_balance'] = $user->client->depositReceived->where('status', 4)->sum('balance');
            $data['client']['total_shared_balance'] = count($user->client->depositReceived->where('status', 4)->where('balance', '>', 0));
            $data['client']['points'] = $user->client->points;
            $data['client']['image_qr'] = $user->username;
            $data['data_car'] = ($user->client->car != null) ? array('number_plate' => $user->client->car->number_plate, 'type_car' => $user->client->car->type_car) : array('number_plate' => null, 'type_car' => null);
            return $this->successResponse('user', $data, null, null);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion principal para la ventana de abonos
    public function getListStations()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            $stations = array();
            foreach (Station::all() as $station) {
                $dataStation['id'] = $station->id;
                $dataStation['name'] = $station->abrev . ' - ' . $station->name;
                $dataStation['number_station'] = $station->number_station;
                $dataStation['address'] = $station->address;
                $dataStation['email'] = $station->email;
                $dataStation['phone'] = $station->phone;
                $dataStation['image'] = asset($station->image);
                array_push($stations, $dataStation);
            }
            $exchanges = array();
            foreach ($user->client->exchanges->where('status', '!=', 14) as $exchange) {
                $dataExchange['station'] = $exchange->station->name;
                $dataExchange['invoice'] = $exchange->exchange;
                $dataExchange['status'] = $exchange->estado->name;
                $dataExchange['date'] = $exchange->created_at->format('Y/m/d');
                array_push($exchanges, $dataExchange);
            }
            return $this->successResponse('stations', $stations, 'exchanges', $exchanges);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            try {
                $payments = array();
                switch ($request->type) {
                    case 'payment':
                        if (count($balances = $this->getBalances(new Sale(), $request->start, $request->end, $user, null, null)) > 0) {
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->payment;
                                $data['station'] = $balance->station->name;
                                $data['liters'] = $balance->liters;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                $data['gasoline'] = $balance->gasoline->name;
                                $data['no_island'] = $balance->no_island;
                                $data['no_bomb'] = $balance->no_bomb;
                                $data['sale'] = $balance->sale;
                                array_push($payments, $data);
                            }
                            return $this->successResponse('payments', $payments, null, null);
                        }
                        break;
                    case 'balance':
                        if (count($balances = $this->getBalances(new Deposit(), $request->start, $request->end, $user, 4, null)) > 0) {
                            foreach ($balances as $balance) {
                                $data['balance'] = $balance->balance;
                                $data['station'] = $balance->station->name;
                                $data['status'] = $balance->deposit->name;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                $data['hour'] = $balance->created_at->format('H:i:s');
                                array_push($payments, $data);
                            }
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break;
                    case 'share':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'transmitter_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'receiver');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break;
                    case 'received':
                        if (count($balances = $this->getBalances(new SharedBalance(), $request->start, $request->end, $user, 4, 'receiver_id')) > 0) {
                            $payments = $this->getSharedBalances($balances, 'transmitter');
                            return $this->successResponse('balances', $payments, null, null);
                        }
                        break;
                    case 'exchange':
                        if (count($balances = $this->getBalances(new Exchange(), $request->start, $request->end, $user, 14, 'exchange')) > 0) {
                            foreach ($balances as $balance) {
                                $data['points'] = $balance->points;
                                $data['station'] = $balance->station->name;
                                $data['invoice'] = $balance->exchange;
                                $data['status'] = $balance->estado->name;
                                $data['status_id'] = $balance->status;
                                $data['date'] = $balance->created_at ? $balance->created_at->format('Y/m/d') : '';
                                array_push($payments, $data);
                            }
                            return $this->successResponse('exchanges', $payments, null, null);
                        }
                        break;
                    case 'points':
                        if (count($balances = $this->getBalances(new SalesQr(), $request->start, $request->end, $user, null, null)) > 0) {
                            foreach ($balances as $balance) {
                                $data['points'] = $balance->points;
                                $data['station'] = $balance->station->name;
                                $data['status'] = ($balance->points == 0) ? 'Intente escanear su ticket nuevamente' : 'Puntos sumados';
                                $data['sale'] = $balance->sale;
                                $data['date'] = $balance->created_at->format('Y/m/d');
                                array_push($payments, $data);
                            }
                            return $this->successResponse('points', $payments, null, null);
                        }
                        break;
                }
                return $this->errorResponse('Sin movimientos en la cuenta');
            } catch (Exception $e) {
                return $this->errorResponse('Error de consulta por fecha');
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Método para ingresar código de referidos
    public function code(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if ($request->code == null)
                return $this->errorResponse('Ingrese un código de referencia');
            $reference = User::where('username', $request->code)->first();
            if ($reference == null)
                return $this->errorResponse('El código no es válido');
            if ($reference->references->contains($user->client->id) || $user->client->reference->count() > 0)
                return $this->errorResponse('Ya se ha ingresado un código anteriormente');
            if ($reference->roles->first()->id != 5 && $reference->roles->first()->id != 6) {
                $reference->references()->attach($user->client->id);
                return $this->successResponse('message', 'Código ingresado correctamente', null, null);
            }
            return $this->errorResponse('El código no es válido');
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
        if ($type == 'exchange') {
            $query = [['client_id', $user->client->id]];
        }
        if ($start == "" && $end == "") {
            $balances = $model::where($query)->get();
        } elseif ($start == "") {
            $balances = $model::where($query)->whereDate('created_at', '<=', $end)->get();
        } elseif ($end == "") {
            $balances = $model::where($query)->whereDate('created_at', '>=', $start)->get();
        } else {
            $balances = ($start > $end) ? null : $model::where($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();
        }
        return ($balances != null) ? $balances->sortByDesc('created_at') : null;
    }
    // Obteniendo el historial enviodo o recibido
    private function getSharedBalances($balances, $person)
    {
        $payments = array();
        foreach ($balances as $balance) {
            $payment['station'] = $balance->station->name;
            $payment['balance'] = $balance->balance;
            $payment['membership'] = $balance->$person->user->username;
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
    private function successResponse($name, $data, $array, $dataArray)
    {
        return ($array != null) ?
            response()->json([
                'ok' => true,
                $name => $data,
                $array => $dataArray
            ]) :
            response()->json([
                'ok' => true,
                $name => $data,
            ]);
    }
}
