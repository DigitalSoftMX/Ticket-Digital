<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DispatcherHistoryPayment;
use App\Gasoline;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RegisterTime;
use App\Schedule;
use App\SharedBalance;
use App\User;
use App\UserHistoryDeposit;
use Exception;
use Illuminate\Support\Facades\Auth;
use XBase\Table;
use Tymon\JWTAuth\Facades\JWTAuth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function main()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            $schedule = Schedule::where('station_id', $user->dispatcher->station->id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
            if (count($time = RegisterTime::where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station->id]])->get()) > 0) {
                $payments = DispatcherHistoryPayment::whereDate('created_at', now()->format('Y-m-d'))->where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station_id], ['time_id', $time[count($time) - 1]->id]])->get();
                $totalPayment = 0;
                foreach ($payments as $payment) {
                    $totalPayment += $payment->payment;
                }
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
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            switch ($request->time) {
                case 'true':
                    $schedule = Schedule::whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->where('station_id', $user->dispatcher->station->id)->first();
                    $time = new RegisterTime();
                    $time->dispatcher_id = $user->dispatcher->id;
                    $time->station_id = $user->dispatcher->station->id;
                    $time->schedule_id = $schedule->id;
                    $time->save();
                    return $this->successResponse('message', 'Inicio de turno registrado');
                case 'false':
                    try {
                        $time = RegisterTime::where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station->id]])->get();
                        $time[count($time) - 1]->updated_at = now();
                        $time[count($time) - 1]->save();
                        return $this->successResponse('message', 'Fin de turno registrado');
                    } catch (Exception $e) {
                        return $this->errorResponse('Turno no registrado');
                    }
            }
            return $this->errorResponse('Registro no valido');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Metodo para obtner la lista de gasolina
    public function gasolineList()
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
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
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            try {
                ini_set("allow_url_fopen", 1);
                $json = $this->curl_get_file_contents('http://' . $user->dispatcher->station->ip . '/sales/public/record.php?bomb_id=' . $request->bomb_id);
                return \json_decode($json, true);
            } catch (Exception $e) {
                return $this->errorResponse('La ip o la bomba son incorrectos');
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para obtener los datos de una venta en Eucomb
    public function dataSale(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            /* accediendo a la lectura del archivo .dbf NOTA:este archivo es dinamico y necesitamos accesos a las estaciones
            para su obtencion y lectura correcta*/
            try {
                $table = new Table('../storage/app/public/TRANS.DBF', null, 'cp1251');
                $sales = array();
                while ($record = $table->nextRecord()) {
                    if ($record->get('bomba') == $request->bomb_id) {
                        $sale['id_gasoline'] = $record->get('prod');
                        $sale['liters'] = $record->get('cant');
                        $sale['price'] = $record->get('importe');
                        $sale['sale'] = $record->get('id_venta');
                        array_push($sales, $sale);
                    }
                }
                return $this->successResponse('sale', $sales[count($sales) - 1]);
            } catch (Exception $e) {
                return $this->errorResponse('Ha ocurrido un error al buscar el registro');
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makeNotification(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            if (($dispatcher = $user->dispatcher)->station_id == $request->id_station) {
                if (($client = User::where('username', $request->membership)->first()) != null) {
                    if ($request->tr_membership == "") {
                        $payment = UserHistoryDeposit::where([['client_id', $client->client->id], ['station_id', $request->id_station], ['balance', '>=', $request->price], ['status', 4]])->first();
                    } else {
                        $transmitter = User::where('username', $request->tr_membership)->first();
                        $payment = SharedBalance::where([['transmitter_id', $transmitter->client->id], ['receiver_id', $client->client->id], ['station_id', $request->id_station], ['balance', '>=', $request->price], ['status', 4]])->first();
                    }
                    if ($payment != null) {
                        $time = RegisterTime::where([['dispatcher_id', $dispatcher->id], ['station_id', $dispatcher->station->id]])->get();
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
                                'id_schedule' => (Schedule::whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->where('station_id', $dispatcher->station_id)->first())->id,
                                'id_station' => $dispatcher->station_id,
                                'tr_membership' => $request->tr_membership,
                                'id_time' => $time[count($time) - 1]->id,
                                'no_island' => $dispatcher->island->island,
                                'no_bomb' => $request->bomb_id,
                                'sale' => $request->sale
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
                    return $this->errorResponse('No hay abonos realizados');
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
            if (count($time = RegisterTime::where([['dispatcher_id', $user->dispatcher->id], ['station_id', $user->dispatcher->station->id]])->get()) > 0) {
                return $this->getPayments(['time_id', $time[count($time) - 1]->id], $user->dispatcher, now()->format('Y-m-d'));
            }
            return $this->errorResponse('Aun no hay registro de cobros');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver la lista de cobros por fecha
    public function getListPayments(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'despachador') {
            return $this->getPayments(['schedule_id', $request->id_schedule], $user->dispatcher, $request->date);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para listar los cobros del depachador
    private function getPayments($array, $dispatcher, $date)
    {
        if (count($payments = DispatcherHistoryPayment::where([['dispatcher_id', $dispatcher->id], ['station_id', $dispatcher->station_id], $array])->whereDate('created_at', $date)->get()) > 0) {
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
    // consulta por uRL
    function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
        else return FALSE;
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
