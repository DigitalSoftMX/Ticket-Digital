<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DispatcherHistoryPayment;
use App\Gasoline;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Schedule;
use App\SharedBalance;
use App\Station;
use App\UserHistoryDeposit;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function main()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            $payments = DispatcherHistoryPayment::where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station_id]])->whereDate('created_at', now()->format('Y-m-d'))->get();
            $totalPayment = 0;
            foreach ($payments as $payment) {
                $totalPayment += $payment->payment;
            }
            $schedule = Schedule::whereTime('start', '<=', now()->format('H:m'))->whereTime('end', '>=', now()->format('H:m'))->where('station_id', $user->dispatcher->station->id)->first();
            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['dispatcher_id'] = $user->dispatcher->dispatcher_id;
            $data['station']['id'] = $user->dispatcher->station->id;
            $data['station']['name'] = $user->dispatcher->station->name;
            $data['station']['number_station'] = $user->dispatcher->station->number_station;
            $data['schedule']['id'] = $schedule->id;
            $data['schedule']['name'] = $schedule->name;
            $data['number_payments'] = count($payments);
            $data['total_payments'] = $totalPayment;
            return $this->successResponse('user', $data);
        }
        return $this->logout(JWTAuth::getToken());
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
            return $this->successResponse('type_gasoline', $data);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makeNotification(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            if (($dispatcher = $user->dispatcher)->station_id == $request->id_station) {
                if (($client = Client::where('membership', $request->membership)->first()) != null) {
                    if ($request->tr_membership == "") {
                        if (($payment = UserHistoryDeposit::where([['client_id', $client->id], ['station_id', $request->id_station]])->first()) != null) {
                            if ($request->price > $payment->balance) {
                                return $this->errorResponse('No hay saldo suficiente');
                            }
                        } else {
                            return $this->errorResponse('No hay abonos realizados en la cuenta');
                        }
                    } else {
                        $transmitter = Client::where('membership', $request->tr_membership)->first();
                        if (($payment = SharedBalance::where([['transmitter_id', $transmitter->id], ['receiver_id', $client->id], ['station_id', $request->id_station]])->first()) != null) {
                            if ($request->price > $payment->balance) {
                                return $this->errorResponse('No hay saldo suficiente');
                            }
                        } else {
                            return $this->errorResponse('No hay abonos realizados');
                        }
                    }
                    $gasoline = Gasoline::find($request->id_gasoline);
                    $fields = array(
                        'app_id' => "91acd53f-d191-4b38-9fa9-2bbbdc95961e",
                        'data' => array(
                            "price" => $request->price,
                            "gasoline" => $gasoline->name,
                            "liters" => $request->liters,
                            "estacion" => $dispatcher->station->name,
                            'ids_dispatcher' => $request->ids_dispatcher,
                            'id_dispatcher' => $dispatcher->id,
                            'id_gasoline' => $request->id_gasoline,
                            'id_schedule' => (Schedule::whereTime('start', '<=', now()->format('H:m'))->whereTime('end', '>=', now()->format('H:m'))->where('station_id', $dispatcher->station_id)->first())->id,
                            'id_station' => $dispatcher->station_id,
                            'tr_membership' => $request->tr_membership
                        ), 'contents' => array(
                            "en" => "English message from postman",
                            "es" => "Realizaste una solicitud de pago."
                        ),
                        'headings' => array(
                            "en" => "English title from postman",
                            "es" => "Pago con QR"
                        ),
                        'include_player_ids' => array("$request->ids_client"),
                    );
                    $fields = json_encode($fields);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    return $this->successResponse('notification', \json_decode($response));
                }
                return $this->errorResponse('Membresía no disponible');
            }
            return $this->errorResponse('Estacion incorrecta');
        }
        return $this->logout(JWTAuth::getToken());
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
            return $this->successResponse('schedules', $dataSchedules);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para obtener los cobros del dia
    public function getPaymentsNow()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            return $this->getPayments(null, $user, now()->format('Y-m-d'));
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver la lista de cobros por fecha
    public function getListPayments(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            return $this->getPayments(['schedule_id', $request->id_schedule], $user, $request->date);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Registro de inicio de turno y termino de turno
    public function startEndTime(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            switch ($request->time) {
                case 'true':
                    return $this->successResponse('message', 'Iniciar turno registrado');
                    break;
                case 'false':
                    return $this->successResponse('message', 'Finalizar turno registrado');
                    break;
                default:
                    return $this->errorResponse('Registro no valido');
            }
        }
        return $this->logout(JWTAuth::getToken());
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
            return $this->successResponse('made_payments', $dataPayment);
        }
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
