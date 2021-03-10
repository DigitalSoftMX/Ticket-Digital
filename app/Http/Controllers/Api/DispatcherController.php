<?php

namespace App\Http\Controllers\Api;

use App\Sale;
use App\Gasoline;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RegisterTime;
use App\Schedule;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function index()
    {
        if (($user = Auth::user())->verifyRole(4)) {
            $schedule = Schedule::where('station_id', $user->dispatcher->station->id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
            if (($time = $user->dispatcher->times->last()) != null) {
                $payments = Sale::whereDate('created_at', now()->format('Y-m-d'))->where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station_id], ['time_id', $time->id]])->get();
                $totalPayment = $payments->sum('payment');
            } else {
                $payments = array();
                $totalPayment = 0;
            }
            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['dispatcher_id'] = $user->username;
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
    // Registro de inicio de turno y termino de turno
    public function startEndTime(Request $request)
    {
        if (($user = Auth::user())->verifyRole(4)) {
            $time = $user->dispatcher->times->last();
            switch ($request->time) {
                case 'true':
                    if ($time != null) {
                        if ($time->status == 6) {
                            return $this->errorResponse('Finalice el turno actual para iniciar otro');
                        }
                    }
                    $schedule = Schedule::whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->where('station_id', $user->dispatcher->station->id)->first();
                    $time = new RegisterTime();
                    $time->create(['dispatcher_id' => $user->dispatcher->id, 'station_id' => $user->dispatcher->station->id, 'schedule_id' => $schedule->id, 'status' => 6]);
                    return $this->successResponse('message', 'Inicio de turno registrado');
                case 'false':
                    if ($time != null) {
                        $time->update(['status' => 8]);
                        return $this->successResponse('message', 'Fin de turno registrado');
                    }
                    return $this->errorResponse('Turno no registrado');
            }
            return $this->errorResponse('Registro no valido');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Metodo para obtner la lista de gasolina
    public function gasolineList()
    {
        if (($user = Auth::user())->verifyRole(4)) {
            $gasolines = array();
            $islands = array();
            foreach (Gasoline::all() as $gasoline) {
                array_push($gasolines, array('id' => $gasoline->id, 'name' => $gasoline->name));
            }
            foreach ($user->dispatcher->station->islands as $island) {
                array_push($islands, array('island' => $island->island, 'bomb' => $island->bomb));
            }
            $data['url'] = 'http://' . $user->dispatcher->station->ip . '/sales/public/record.php';
            $data['islands'] = $islands;
            $data['gasolines'] = $gasolines;
            return $this->successResponse('data', $data);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Obteniendo el valor de venta por bomba
    public function getSale(Request $request)
    {
        if (($user = Auth::user())->verifyRole(4)) {
            try {
                ini_set("allow_url_fopen", 1);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_URL, 'http://' . $user->dispatcher->station->ip . '/sales/public/record.php?bomb_id=' . $request->bomb_id);
                $contents = curl_exec($curl);
                curl_close($curl);
                if ($contents) {
                    return \json_decode($contents, true);
                }
                return $this->errorResponse('Intente más tarde');
            } catch (Exception $e) {
                return $this->errorResponse('La ip o la bomba son incorrectos');
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makeNotification(Request $request)
    {
        if (($user = Auth::user())->verifyRole(4)) {
            if (($dispatcher = $user->dispatcher)->station_id == $request->id_station) {
                if (Sale::where([['sale', $request->sale], ['station_id', $request->id_station]])->exists()) {
                    return $this->errorResponse('La venta fue registrada anteriormente');
                }
                if (($client = User::where('username', $request->membership)->first()) != null) {
                    if ($request->tr_membership == "") {
                        $deposit = $client->client->deposits->where('status', 4)->where('station_id', $request->id_station)->where('balance', '>=', $request->price)->first();
                    } else {
                        if (($transmitter = User::where('username', $request->tr_membership)->first()) == null) {
                            return $this->errorResponse('La membresía del receptor no esta disponible');
                        }
                        $deposit = $client->client->depositReceived->where('transmitter_id', $transmitter->client->id)->where('station_id', $request->id_station)->where('balance', '>=', $request->price)->where('status', 4)->first();
                    }
                    if ($deposit != null) {
                        $gasoline = Gasoline::find($request->id_gasoline);
                        $no_island = null;
                        try {
                            $no_island = $dispatcher->station->islands->where('bomb', $request->bomb_id)->first()->island;
                        } catch (Exception $e) {
                        }
                        $fields = array(
                            'app_id' => "62450fc4-bb2b-4f2e-a748-70e8300c6ddb",
                            'data' => array(
                                'id_dispatcher' => $dispatcher->id,
                                'sale' => $request->sale,
                                'id_gasoline' => $gasoline->id,
                                "liters" => $request->liters,
                                "price" => $request->price,
                                'id_schedule' => (Schedule::whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->where('station_id', $dispatcher->station_id)->first())->id,
                                'id_station' => $dispatcher->station_id,
                                'id_time' => $dispatcher->times->last()->id,
                                'no_island' => $no_island,
                                'no_bomb' => $request->bomb_id,
                                "gasoline" => $gasoline->name,
                                "estacion" => $dispatcher->station->name,
                                'ids_dispatcher' => $request->ids_dispatcher,
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
                    return $this->errorResponse('Saldo insuficiente');
                }
                return $this->errorResponse('Membresía no disponible');
            }
            return $this->errorResponse('Estación incorrecta');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para obtener la lista de horarios de una estacion
    public function getListSchedules()
    {
        if (($user = Auth::user())->verifyRole(4)) {
            $dataSchedules = array();
            foreach ($user->dispatcher->station->schedules as $schedule) {
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
        if (($user = Auth::user())->verifyRole(4)) {
            if (($time = $user->dispatcher->times->last()) != null) {
                return $this->getPayments(['time_id', $time->id], $user->dispatcher, now()->format('Y-m-d'));
            }
            return $this->errorResponse('Aun no hay registro de cobros');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver la lista de cobros por fecha
    public function getListPayments(Request $request)
    {
        if (($user = Auth::user())->verifyRole(4)) {
            return $this->getPayments(['schedule_id', $request->id_schedule], $user->dispatcher, $request->date);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para listar los cobros del depachador
    private function getPayments($array, $dispatcher, $date)
    {
        if (count($payments = Sale::where([['dispatcher_id', $dispatcher->id], ['station_id', $dispatcher->station_id], $array])->whereDate('created_at', $date)->get()) > 0) {
            $dataPayment = array();
            $magna = 0;
            $premium = 0;
            $diesel = 0;
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
                switch ($payment->gasoline->name) {
                    case 'Magna':
                        $magna += $payment->liters;
                        break;
                    case 'Premium':
                        $premium += $payment->liters;
                        break;
                    case 'Diésel':
                        $diesel += $payment->liters;
                        break;
                }
            }
            $info['liters_product'] = array('Magna' => $magna, 'Premium' => $premium, 'Diésel' => $diesel);
            $info['payment'] = $dataPayment;
            return $this->successResponse('payments', $info);
        }
        return $this->errorResponse('Aun no hay registro de cobros');
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
        return response()->json(['ok' => false, 'message' => $message]);
    }
    // Funcion mensaje correcto
    private function successResponse($name, $data)
    {
        return response()->json(['ok' => true, $name => $data]);
    }
}
