<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DispatcherHistoryPayment;
use App\Gasoline;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Schedule;
use App\SharedBalance;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function main()
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $user = Auth::user();
            $payments = DispatcherHistoryPayment::where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station_id]])->whereDate('created_at', now()->format('Y-m-d'))->get();
            $totalPayment = 0;
            foreach ($payments as $payment) {
                $totalPayment += $payment->payment;
            }
            $schedule = Schedule::whereTime('start', '<=', now()->format('H:m'))->whereTime('end', '>=', now()->format('H:m'))->where('station_id', $user->dispatcher->station->id)->first();
            $data = array(
                'id' => $user->id,
                'name' => $user->name,
                'first_surname' => $user->first_surname,
                'second_surname' => $user->second_surname,
                'dispatcher_id' => $user->dispatcher->dispatcher_id,
                'station' => array(
                    'id' => $user->dispatcher->station->id,
                    'name' => $user->dispatcher->station->name,
                    'number_station' => $user->dispatcher->station->number_station
                ),
                'schedule' => array(
                    'id' => $schedule->id,
                    'name' => $schedule->name
                ),
                'number_payments' => count($payments),
                'total_payments' => $totalPayment
            );
            return $this->successMessage('user', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Metodo para obtner la lista de gasolina
    public function gasolineList()
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $data = array();
            foreach (Gasoline::all() as $gasoline) {
                $dataGasoline = array('id' => $gasoline->id, 'name' => $gasoline->name);
                array_push($data, $dataGasoline);
            }
            return $this->successMessage('type_gasoline', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makePayment(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            if (($dispatcher = Auth::user()->dispatcher)->station_id == $request->id_station) {
                $client = Client::where('membership', $request->membership)->first();
                if ($request->tr_membership == "") {
                    if (($payment = UserHistoryDeposit::where([['client_id', $client->id], ['station_id', $request->id_station]])->first()) != null) {
                        if ($request->price <= $payment->balance) {
                            $payment->balance -= $request->price;
                            $payment->save();
                            $client->current_balance -= $request->price;
                            $client->points += intval($request->liters);
                            $client->save();
                        } else {
                            return $this->errorMessage('No hay saldo suficiente');
                        }
                    } else {
                        return $this->errorMessage('No hay abonos realizados en la cuenta');
                    }
                } else {
                    $transmitter = Client::where('membership', $request->tr_membership)->first();
                    if (($payment = SharedBalance::where([['transmitter_id', $transmitter->id], ['receiver_id', $client->id], ['station_id', $request->id_station]])->first()) != null) {
                        if ($request->price <= $payment->balance) {
                            $payment->balance -= $request->price;
                            $payment->save();
                            $client->shared_balance -= $request->price;
                            $client->save();
                            $transmitter->points += intval($request->liters);
                            $transmitter->save();
                        } else {
                            return $this->errorMessage('No hay saldo suficiente');
                        }
                    } else {
                        return $this->errorMessage('No hay abonos realizados');
                    }
                }
                // Registro de pagos para historial del despachador
                $registerPayment = new DispatcherHistoryPayment();
                $registerPayment->dispatcher_id = $dispatcher->id;
                $registerPayment->gasoline_id = $request->id_gasoline;
                $registerPayment->liters = $request->liters;
                $registerPayment->payment = $request->price;
                $registerPayment->schedule_id = (Schedule::whereTime('start', '<=', now()->format('H:m'))->whereTime('end', '>=', now()->format('H:m'))->where('station_id', Auth::user()->dispatcher->station_id)->first())->id;
                $registerPayment->station_id = $dispatcher->station_id;
                $registerPayment->client_id = $client->id;
                $registerPayment->save();
                return $this->successMessage('payment', 'Cobro realizado correctamente');
            } else {
                return $this->errorMessage('Estacion incorrecta');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para obtener la lista de horarios de una estacion
    public function getListSchedules()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            $schedules = Schedule::where('station_id', $user->dispatcher->station_id)->get();
            $dataSchedules = array();
            foreach ($schedules as $schedule) {
                $data = array('id' => $schedule->id, 'name' => $schedule->name);
                array_push($dataSchedules, $data);
            }
            return $this->successMessage('schedules', $dataSchedules);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para obtener los cobros del dia
    public function getPaymentsNow()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            return $this->getPayments(null, $user, now()->format('Y-m-d'));
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para devolver la lista de cobros por fecha
    public function getListPayments(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            return $this->getPayments(['schedule_id', $request->id_schedule], $user, $request->date);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para listar los cobros del depachador
    private function getPayments($array, $user, $date)
    {
        $query = [['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station_id]];
        if ($array != null) {
            $query[2] = $array;
        }
        if (count($payments = DispatcherHistoryPayment::where($query)->whereDate('created_at', $date)->get()) > 0) {
            $dataPayment = array();
            foreach ($payments as $payment) {
                $data = array(
                    'id' => $payment->id,
                    'payment' => $payment->payment,
                    'gasoline' => $payment->gasoline->name,
                    'liters' => $payment->liters,
                    'date' => $payment->created_at->format('Y/m/d'),
                    'hour' => $payment->created_at->format('H:i:s')
                );
                array_push($dataPayment, $data);
            }
            return $this->successMessage('made_payments', $dataPayment);
        } else {
            return $this->errorMessage('No hay cobros realizados');
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
}
