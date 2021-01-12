<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\User;
use App\Deposit;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

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
                                $points = 0;
                                foreach (Sale::where([['client_id', $user->client->id], ['transmitter_id', null]])->whereDate('created_at', now()->format('Y-m-d'))->get() as $payment) {
                                    $points += $this->roundHalfDown($payment->liters);
                                }
                                if ($points > 80) {
                                    $points -= $this->roundHalfDown($user->client->payments->last()->liters);
                                    if ($points <= 80) {
                                        $points = 80 - $points;
                                    } else {
                                        $points = 0;
                                    }
                                } else {
                                    $points = $this->roundHalfDown($request->liters);
                                }
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
