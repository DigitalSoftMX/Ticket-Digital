<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\User;
use App\Deposit;
use App\Empresa;
use App\Station;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class BalanceController extends Controller
{
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (count($payments = $user->client->deposits->where('status', 4)->where('balance', '>', 0)) > 0) {
                $deposits = array();
                foreach ($payments as $payment) {
                    $data['id'] = $payment->id;
                    $data['balance'] = $payment->balance;
                    $data['status'] = $payment->status;
                    $data['station']['name'] = $payment->station->name;
                    $data['station']['number_station'] = $payment->station->number_station;
                    array_push($deposits, $data);
                }
                return $this->successResponse('payments', $deposits);
            }
            return $this->errorResponse('No hay abonos realizados');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if ($request->deposit % 100 == 0 && $request->deposit > 0) {
                // Obteniendo el archivo de imagen de pago
                if (($file = $request->file('image')) != NULL) {
                    if ((strpos($file->getClientMimeType(), 'image')) === false) {
                        return $this->errorResponse('El archivo no es una imagen');
                    }
                    $request->merge(['client_id' => $user->client->id, 'balance' => $request->deposit, 'image_payment' => $request->file('image')->store($user->username . '/' . $request->id_station, 'public'), 'station_id' => $request->id_station, 'status' => 1]);
                    $deposit = new Deposit();
                    $deposit->create($request->all());
                    return $this->successResponse('message', 'Abono realizado correctamente');
                }
                return $this->errorResponse('Debe subir su comprobante');
            }
            return $this->errorResponse('La cantidad debe ser multiplo de $100');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver la membresía del cliente y la estacion
    public function useBalance(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (($payment = $user->client->deposits->find($request->id_payment)) != null) {
                $station['id'] = $payment->station->id;
                $station['name'] = $payment->station->name;
                $station['number_station'] = $payment->station->number_station;
                return response()->json([
                    'ok' => true,
                    'membership' => $user->username,
                    'station' => $station
                ]);
            }
            return $this->errorResponse('No hay abono en la cuenta');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if ($request->balance % 100 == 0 && $request->balance > 0) {
                // Obteniendo el saldo disponible en la estacion correspondiente
                $payment = $user->client->deposits->find($request->id_payment);
                if ($payment != null && $payment->status == 4) {
                    if ($payment->balance >= $request->balance) {
                        $request->merge(['transmitter_id' => $user->client->id, 'receiver_id' => $request->id_contact, 'station_id' => $payment->station_id, 'status' => 5]);
                        $sharedBalance = new SharedBalance();
                        $sharedBalance->create($request->all());
                        if (($receivedBalance = SharedBalance::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id], ['status', 4]])->first()) != null) {
                            $receivedBalance->balance += $request->balance;
                            $receivedBalance->save();
                        } else {
                            $sharedBalance = new SharedBalance();
                            $sharedBalance->create($request->merge(['status' => 4])->all());
                        }
                        $payment->balance -= $request->balance;
                        $payment->save();
                        $this->makeNotification(Client::find($request->id_contact)->ids, null, 'Te han compartido saldo', 'Saldo compartido');
                        return $this->successResponse('message', 'Saldo compartido correctamente');
                    }
                    return $this->errorResponse('Saldo insuficiente');
                }
                return $this->errorResponse('Saldo no disponible');
            }
            return $this->errorResponse('La cantidad debe ser multiplo de $100');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion que busca los abonos recibidos
    public function listReceivedPayments()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (count($balances = $user->client->depositReceived->where('status', 4)->where('balance', '>', 0)) > 0) {
                $receivedBalances = array();
                foreach ($balances as $balance) {
                    $data['id'] = $balance->id;
                    $data['balance'] = $balance->balance;
                    $data['station']['name'] = $balance->station->name;
                    $data['station']['number_station'] = $balance->station->number_station;
                    $data['transmitter']['membership'] = $balance->transmitter->user->username;
                    $data['transmitter']['user']['name'] = $balance->transmitter->user->name;
                    $data['transmitter']['user']['first_surname'] = $balance->transmitter->user->first_surname;
                    $data['transmitter']['user']['second_surname'] = $balance->transmitter->user->second_surname;
                    array_push($receivedBalances, $data);
                }
                return $this->successResponse('payments', $receivedBalances);
            }
            return $this->errorResponse('No hay abonos realizados');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para devolver informacion de un saldo compartido
    public function useSharedBalance(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (($deposit = $user->client->depositReceived->find($request->id_payment)) != null) {
                $station['id'] = $deposit->station->id;
                $station['name'] = $deposit->station->name;
                $station['number_station'] = $deposit->station->number_station;
                return response()->json([
                    'ok' => true,
                    'tr_membership' => $deposit->transmitter->user->username,
                    'membership' => $deposit->receiver->user->username,
                    'station' => $station
                ]);
            }
            return $this->errorResponse('No hay abono en la cuenta');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para realizar un pago autorizado por el cliente
    public function makePayment(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if ($request->authorization == "true") {
                try {
                    $request->merge(['dispatcher_id' => $request->id_dispatcher, 'gasoline_id' => $request->id_gasoline, 'payment' => $request->price, 'schedule_id' => $request->id_schedule, 'station_id' => $request->id_station, 'client_id' => $user->client->id, 'time_id' => $request->id_time]);
                    if ($request->tr_membership == "") {
                        if (($deposit = $user->client->deposits->where('status', 4)->where('station_id', $request->id_station)->where('balance', '>=', $request->price)->first()) != null) {
                            $sale = new Sale();
                            $sale->create($request->all());
                            $deposit->balance -= $request->price;
                            $deposit->save();
                            if ($request->id_gasoline != 3) {
                                $points = $this->addEightyPoints(Sale::where([['client_id', $user->client->id], ['transmitter_id', null]])->whereDate('created_at', now()->format('Y-m-d'))->get(), $user->client->payments->last()->liters, $request->liters);
                                $user->client->points += $points;
                                $user->client->save();
                            }
                        } else {
                            return $this->errorResponse('Saldo insuficiente');
                        }
                    } else {
                        $transmitter = User::where('username', $request->tr_membership)->first();
                        if (($payment = $user->client->depositReceived->where('transmitter_id', $transmitter->client->id)->where('station_id', $request->id_station)->where('status', 4)->where('balance', '>=', $request->price)->first()) != null) {
                            $sale = new Sale();
                            $sale->create($request->merge(['transmitter_id' => $transmitter->client->id])->all());
                            $payment->balance -= $request->price;
                            $payment->save();
                            if ($request->id_gasoline != 3) {
                                $transmitter->client->points += $this->roundHalfDown($request->liters);
                                $transmitter->client->save();
                            }
                        } else {
                            return $this->errorResponse('Saldo insuficiente');
                        }
                    }
                    return $this->makeNotification($request->ids_dispatcher, $request->ids_client, 'Cobro realizado con éxito', 'Pago con QR');
                } catch (Exception $e) {
                    return $this->errorResponse('Error al registrar el cobro');
                }
            }
            return $this->makeNotification($request->ids_dispatcher, null, 'Cobro cancelado', 'Pago con QR');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Metoodo para sumar de puntos QR o formulario
    public function addPoints(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if ($request->qr != '') {
                $request->merge(['code' => substr($request->qr, 0, 15), 'station' => substr($request->qr, 15, 5), 'sale' => substr($request->qr, 20)]);
            }
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|min:15',
                'station' => 'required|string|min:5',
                'sale' => 'required|string',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), null);
            }
            if (($station = Station::where('number_station', $request->station)->first()) != null) {
                if (Sale::where([['sale', $request->sale], ['station_id', $station->id]])->exists()) {
                    return $this->errorResponse('Esta venta fue registrado anteriormente');
                }
                if (count(Sale::where([['dispatcher_id', null], ['client_id', $user->client->id]])->whereDate('created_at', now()->format('Y-m-d'))->get()) < 4) {
                    try {
                        ini_set("allow_url_fopen", 1);
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_URL, 'http://localhost/sales/public/points.php?sale=' . $request->sale . '&code=' . $request->code);
                        // curl_setopt($curl, CURLOPT_URL, 'http://' . $station->ip . '/sales/public/points.php?sale=' . $request->sale . '&code=' . $request->code);
                        $contents = curl_exec($curl);
                        curl_close($curl);
                        if ($contents) {
                            $sale = \json_decode($contents, true);
                            switch ($sale['validation']) {
                                case 2:
                                    return $this->errorResponse('El código es incorrecto');
                                case 3:
                                    return $this->errorResponse('Intente más tarde');
                                case 404:
                                    return $this->errorResponse('El id de venta no existe');
                            }
                            if ($sale['gasoline_id'] == 3) {
                                return $this->errorResponse('La suma de puntos no aplica para el producto diésel');
                            }
                            $dateSale = new DateTime('2021-01-14 18:27');
                            $start = $dateSale->modify('+10 minute');
                            $dateSale = new DateTime('2021-01-14 18:27');
                            $dateSale->modify('+10 minute');
                            $end = $dateSale->modify('+24 hours');
                            /* $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                            $start = $dateSale->modify('+10 minute');
                            $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                            $dateSale->modify('+10 minute');
                            $end = $dateSale->modify('+24 hours'); */
                            if (now() < $start) {
                                return $this->errorResponse("Escanee su QR 10 minutos despues de su compra");
                            }
                            if (now() > $end) {
                                return $this->errorResponse('Han pasado 24 hrs para escanear su QR');
                            }
                            if ($sale['status'] == 'L' || $sale['status'] == 'l') {
                                return $this->errorResponse('Esta venta pertenece a otro programa de recompensas');
                            }
                            $request->merge($sale);
                            try {
                                $request->merge(['no_island' => $station->islands->where('bomb', $request->no_bomb)->first()->island]);
                            } catch (Exception $e) {
                            }
                            $request->merge(['station_id' => $station->id, 'client_id' => $user->client->id]);
                            $qr = new Sale();
                            $qr = $qr->create($request->all());
                            $points = $this->addEightyPoints(Sale::where([['client_id', $user->client->id], ['transmitter_id', null]])->whereDate('created_at', now()->format('Y-m-d'))->get(), $user->client->payments->last()->liters, $request->liters);
                            if ($points == 0) {
                                $qr->delete();
                                $limit = (Empresa::find(1)->double_points) * 80;
                                return $this->errorResponse("Ha llegado al limite de $limit puntos por día");
                            }
                            $user->client->points += $points;
                            $user->client->save();
                            return $this->successResponse('points', "Se han sumado sus puntos correctamente");
                        }
                        return $this->errorResponse('Intente más tarde');
                    } catch (Exception $e) {
                        return $this->errorResponse('Intente más tarde');
                    }
                }
                return $this->errorResponse('Solo puedes validar 4 QR\'s por día');
            }
            return $this->errorResponse('La estación no existe');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Método para realizar canjes
    public function exchange()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if($user->client->points){
                
            }
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para enviar una notificacion
    private function makeNotification($idsDispatcher, $idsClient, $message, $notification)
    {
        $ids = array("$idsDispatcher");
        if ($idsClient != null) {
            $ids = array("$idsDispatcher", "$idsClient");
            $notification = '';
        }
        $fields = array(
            'app_id' => "91acd53f-d191-4b38-9fa9-2bbbdc95961e",
            'contents' => array(
                "en" => "English message from postman",
                "es" => $message
            ),
            'headings' => array(
                "en" => "English title from postman",
                "es" => $notification
            ),
            'include_player_ids' => $ids,
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
    // Metodo para calcular puntos
    private function addEightyPoints($payments, $lastLiters, $liters)
    {
        $points = 0;
        foreach ($payments as $payment) {
            $points += $this->roundHalfDown($payment->liters);
        }
        $limit = Empresa::find(1)->double_points;
        if ($points > (80 * $limit)) {
            $points -= $this->roundHalfDown($lastLiters);
            if ($points <= (80 * $limit)) {
                $points = (80 * $limit) - $points;
            } else {
                $points = 0;
            }
        } else {
            $points = $this->roundHalfDown($liters);
        }
        return $points;
    }
    // Funcion redonde de la mitad hacia abajo
    private function roundHalfDown($val)
    {
        $liters = explode(".", $val);
        if (count($liters) > 1) {
            $newVal = $liters[0] . '.' . $liters[1][0];
            $newVal = round($newVal, 0, PHP_ROUND_HALF_DOWN);
        } else {
            $newVal = intval($val);
        }
        return $newVal;
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
    // Funcion mensaje correcto
    private function successResponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Funcion mensajes de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
