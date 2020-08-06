<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DispatcherHistoryPayment;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use Illuminate\Support\Facades\Auth;
use Exception;

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
            $data['client']['total_shared_balance'] = count(SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0]])->get());
            $data['client']['points'] = $user->client->points;
            $data['client']['image_qr'] = $user->client->image_qr;
            $data['data_car'] = $dataCar;
            return $this->successResponse('user', $data);
        }
        return $this->errorResponse('Usuario no autorizado');
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
        return $this->errorResponse('Usuario no autorizado');
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            try {
                if (($type = $request->type) == 'payment') {
                    if (count($balances = $this->getBalances(new DispatcherHistoryPayment(), $request->start, $request->end, $user, null)) > 0) {
                        $payments = array();
                        foreach ($balances as $balance) {
                            $data['balance'] = $balance->payment;
                            $data['station'] = $balance->station->name;
                            $data['liters'] = $balance->liters;
                            $data['date'] = $balance->created_at->format('Y/m/d');
                            $data['hour'] = $balance->created_at->format('H:i:s');
                            $data['gasoline'] = $balance->gasoline->name;
                            array_push($payments, $data);
                        }
                        return $this->successResponse('payments', $payments);
                    }
                } else {
                    if (count($balances = $this->getBalances(new History(), $request->start, $request->end, $user, $type)) > 0) {
                        $payments = array();
                        switch ($type) {
                            case 'balance':
                                foreach ($balances as $balance) {
                                    $payment = json_decode($balance->action);
                                    $station = Station::find($payment->station_id);
                                    $data['balance'] = $payment->balance;
                                    $data['station'] = $station->name;
                                    $data['date'] = $balance->created_at->format('Y/m/d');
                                    $data['hour'] = $balance->created_at->format('H:i:s');
                                    array_push($payments, $data);
                                }
                                break;
                            case 'share':
                                $payments = $this->getSharedBalances($balances, 'receiver_id');
                                break;
                            case 'received':
                                $payments = $this->getSharedBalances($balances, 'transmitter_id');
                                break;
                        }
                        return $this->successResponse('balances', $payments);
                    }
                }
                return $this->errorResponse('Sin movimientos en la cuenta');
            } catch (Exception $e) {
                return $this->errorResponse('Error de consulta por fecha');
            }
        }
        return $this->errorResponse('Usuario no autorizado');
    }
    // Funcion para devolver el arreglo de historiales
    private function getBalances($model, $start, $end, $user, $type)
    {
        $query = [['client_id', $user->client->id]];
        if ($type != null) {
            $query[1] = ['type', $type];
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
            $action = json_decode($balance->action);
            $station = Station::find($action->station_id);
            $client = Client::find($action->$person);
            $payment['station'] = $station->name;
            $payment['balance'] = $action->balance;
            $payment['membership'] = $client->membership;
            $payment['name'] = $client->user->name . ' ' . $client->user->first_surname . ' ' . $client->user->second_surname;
            $payment['date'] = $balance->created_at->format('Y/m/d');
            array_push($payments, $payment);
        }
        return $payments;
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
