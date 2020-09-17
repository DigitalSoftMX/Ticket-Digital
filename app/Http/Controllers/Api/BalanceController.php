<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DispatcherHistoryPayment;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\UserHistoryDeposit;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class BalanceController extends Controller
{
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($payments = UserHistoryDeposit::where([['client_id', $user->client->id], ['balance', '>', 0], ['status', 4]])->get()) > 0) {
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
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($request->deposit % 100 == 0 && $request->deposit > 0) {
                // Obteniendo el archivo de imagen de pago
                if (($file = $request->file('image')) != NULL) {
                    if ((strpos($file->getClientMimeType(), 'image')) === false) {
                        return $this->errorResponse('El archivo no es una imagen');
                    }
                    $history = new UserHistoryDeposit();
                    $history->client_id = $user->client->id;
                    $history->balance = $request->deposit;
                    $history->points = 0;
                    $history->image_payment = $request->file('image')->store($user->client->membership . '/' . $request->id_station, 'public');
                    $history->station_id = $request->id_station;
                    $history->status = 1;
                    $history->save();
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
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            $payment = UserHistoryDeposit::find($request->id_payment);
            if ($payment != null && $payment->client_id == $user->client->id) {
                $station['id'] = $payment->station->id;
                $station['name'] = $payment->station->name;
                $station['number_station'] = $payment->station->number_station;
                return response()->json([
                    'ok' => true,
                    'membership' => $user->client->membership,
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
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($request->balance % 100 == 0 && $request->balance > 0) {
                // Obteniendo el saldo disponible en la estacion correspondiente
                $payment = UserHistoryDeposit::find($request->id_payment);
                if ($payment != null && $payment->client_id == $user->client->id && $payment->status == 4) {
                    if (!($request->balance > $payment->balance)) {
                        $this->registerSharedBalance($user, $request, $payment, 5);
                        if (($receivedBalance = SharedBalance::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id], ['status', 4]])->first()) != null) {
                            $receivedBalance->balance += $request->balance;
                            $receivedBalance->save();
                        } else {
                            $this->registerSharedBalance($user, $request, $payment, 4);
                        }
                        $payment->balance -= $request->balance;
                        $payment->save();
                        // Actualizando el abono total del cliente emisor
                        $user->client->current_balance -= $request->balance;
                        $user->client->save();
                        // Acutalizando el abono compartido del cliente receptor
                        $receiverUser = Client::find($request->id_contact);
                        $receiverUser->shared_balance += $request->balance;
                        $receiverUser->save();
                        return $this->successResponse('message', 'Abono realizado correctamente');
                    }
                    return $this->errorResponse('El deposito es mayor al disponible');
                }
                return $this->errorResponse('El deposito no corresponde al usuario');
            }
            return $this->errorResponse('La cantidad debe ser multiplo de $100');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion que busca los abonos recibidos
    public function listReceivedPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($balances = SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0], ['status', 4]])->get()) > 0) {
                $receivedBalances = array();
                foreach ($balances as $balance) {
                    $data['id'] = $balance->id;
                    $data['balance'] = $balance->balance;
                    $data['station']['name'] = $balance->station->name;
                    $data['station']['number_station'] = $balance->station->number_station;
                    $data['transmitter']['membership'] = $balance->transmitter->membership;
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
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            $payment = SharedBalance::find($request->id_payment);
            if ($payment != null && $payment->receiver_id == $user->client->id) {
                $station['id'] = $payment->station->id;
                $station['name'] = $payment->station->name;
                $station['number_station'] = $payment->station->number_station;
                return response()->json([
                    'ok' => true,
                    'tr_membership' => $payment->transmitter->membership,
                    'membership' => $payment->receiver->membership,
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
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($request->authorization == "true") {
                try {
                    if ($request->tr_membership == "") {
                        if (($payment = UserHistoryDeposit::where([['client_id', $user->client->id], ['station_id', $request->id_station], ['balance', '>=', $request->price]])->first()) != null) {       
                            $this->registerPayment($request, $user->client->id, $payment);
                            $user->client->current_balance -= $request->price;
                            if ($request->id_gasoline != 3) {
                                $user->client->points += $this->roundHalfDown($request->liters);
                                $payment->points += $this->roundHalfDown($request->liters);
                            }
                            $payment->save();
                            $user->client->save();
                        } else {
                            return $this->errorResponse('Saldo insuficiente');
                        }
                    } else {
                        $transmitter = Client::where('membership', $request->tr_membership)->first();
                        if (($payment = SharedBalance::where([['transmitter_id', $transmitter->id], ['receiver_id', $user->client->id], ['station_id', $request->id_station], ['balance', '>=', $request->price]])->first()) != null) {
                            $this->registerPayment($request, $user->client->id, $payment);
                            $payment->save();
                            $user->client->shared_balance -= $request->price;
                            $user->client->save();
                            if ($request->id_gasoline != 3) {
                                $transmitter->points += $this->roundHalfDown($request->liters);
                                $points = UserHistoryDeposit::where([['client_id', $transmitter->id], ['station_id', $request->id_station]])->first();
                                $points->points += $this->roundHalfDown($request->liters);
                                $points->save();
                            }
                            $transmitter->save();
                        } else {
                            return $this->errorResponse('Saldo insuficiente');
                        }
                    }
                    return $this->makeNotification($request->ids_dispatcher, $request->ids_client);
                } catch (Exception $e) {
                    return $this->errorResponse('Error al registrar el cobro');
                }
            }
            return $this->makeNotification($request->ids_dispatcher, null);
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para registrar un saldo compartido
    private function registerSharedBalance($user, $request, $payment, $status)
    {
        $sharedBalance = new SharedBalance();
        $sharedBalance->transmitter_id = $user->client->id;
        $sharedBalance->receiver_id = $request->id_contact;
        $sharedBalance->balance = $request->balance;
        $sharedBalance->station_id = $payment->station_id;
        $sharedBalance->status = $status;
        $sharedBalance->save();
    }
    // Funcion para guardar historial de abonos a la cuenta del cliente
    private function saveHistoryBalance($history, $type, $balance)
    {
        $historyBalance = new History();
        if ($balance != null) {
            $history->balance = $balance;
        }
        switch ($type) {
            case 'balance':
                $historyBalance->client_id = $history->client_id;
                break;
            case 'share':
                $historyBalance->client_id = $history->transmitter_id;
                break;
            case 'received':
                $historyBalance->client_id = $history->receiver_id;
                break;
        }
        $historyBalance->action = $history;
        $historyBalance->type = $type;
        $historyBalance->save();
    }
    // Funcion para registrar los pagos
    private function registerPayment($request, $id, $payment)
    {
        $registerPayment = new DispatcherHistoryPayment();
        $registerPayment->dispatcher_id = $request->id_dispatcher;
        $registerPayment->gasoline_id = $request->id_gasoline;
        $registerPayment->liters = $request->liters;
        $registerPayment->payment = $request->price;
        $registerPayment->schedule_id = $request->id_schedule;
        $registerPayment->station_id = $request->id_station;
        $registerPayment->client_id = $id;
        $registerPayment->time_id = $request->id_time;
        $registerPayment->no_island = $request->no_island;
        $registerPayment->no_bomb = $request->no_bomb;
        $registerPayment->save();
        $payment->balance -= $request->price;
    }
    // Funcion para enviar una notificacion
    private function makeNotification($idsDispatcher, $idsClient)
    {
        $ids = array("$idsDispatcher");
        $success = false;
        $message = 'Cobro cancelado';
        if ($idsClient != null) {
            $ids = array("$idsDispatcher", "$idsClient");
            $success = true;
            $message = 'Cobro realizado con exito';
        }
        $fields = array(
            'app_id' => "91acd53f-d191-4b38-9fa9-2bbbdc95961e",
            'data' => array(
                'success' => $success
            ), 'contents' => array(
                "en" => "English message from postman",
                "es" => $message
            ),
            'headings' => array(
                "en" => "English title from postman",
                "es" => "Pago con QR"
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
