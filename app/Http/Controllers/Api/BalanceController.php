<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($payments = UserHistoryDeposit::where([['client_id', $user->client->id], ['balance', '>', 0]])->get()) > 0) {
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
        return $this->errorResponse('Usuario no autorizado');
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            // Comprobar que el saldo sea un multiplo de 100 y mayor a cero
            if ($request->deposit % 100 == 0 && $request->deposit > 0) {
                // Obteniendo el archivo de imagen de pago
                if (($file = $request->file('image')) != NULL) {
                    if ((strpos($file->getClientMimeType(), 'image')) === false) {
                        return $this->errorResponse('El archivo no es una imagen');
                    }
                    //obtenemos el nombre del archivo
                    // $nombre = $file->getClientOriginalName();      
                    /* Falta guardar la imagen de pago
                    El nombre de la imagen prodria ser el la membresia del cliente y la estacion   */
                    if (($balance = UserHistoryDeposit::where([['client_id', $user->client->id], ['station_id', $request->id_station]])->first()) != null) {
                        $balance->balance += $request->deposit;
                        $balance->status = 1;
                        $balance->save();
                        $this->saveHistoryBalance($balance, 'balance', $request->deposit);
                    } else {
                        $history = new UserHistoryDeposit();
                        $history->client_id = $user->client->id;
                        $history->balance = $request->deposit;
                        $history->station_id = $request->id_station;
                        $history->status = 1;
                        $history->save();
                        $this->saveHistoryBalance($history, 'balance', null);
                    }
                    $user->client->current_balance += $request->deposit;
                    $user->client->update();
                    return $this->successResponse('message', 'Abono realizado correctamente');
                }
                return $this->errorResponse('Debe subir su comprobante');
            }
            return $this->errorResponse('La cantidad debe ser multiplo de $100');
        }
        return $this->errorResponse('Usuario no autorizado');
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
        return $this->errorResponse('Usuario no autorizado');
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($request->balance % 100 == 0 && $request->balance > 0) {
                // Obteniendo el saldo disponible en la estacion correspondiente
                $payment = UserHistoryDeposit::find($request->id_payment);
                if ($payment != null && $payment->client_id == $user->client->id) {
                    if (!($request->balance > $payment->balance)) {
                        if (($receivedBalance = SharedBalance::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id]])->first()) != null) {
                            $receivedBalance->balance += $request->balance;
                            $receivedBalance->status = 1;
                            $receivedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($receivedBalance, 'share', $request->balance);
                            $this->saveHistoryBalance($receivedBalance, 'received', $request->balance);
                        } else {
                            $sharedBalance = new SharedBalance();
                            $sharedBalance->transmitter_id = $user->client->id;
                            $sharedBalance->receiver_id = $request->id_contact;
                            $sharedBalance->balance = $request->balance;
                            $sharedBalance->station_id = $payment->station_id;
                            $sharedBalance->status = 1;
                            $sharedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($sharedBalance, 'share', null);
                            $this->saveHistoryBalance($sharedBalance, 'received', null);
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
        return $this->errorResponse('Usuario no autorizado');
    }
    // Funcion que busca los abonos recibidos
    public function listReceivedPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($balances = SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0]])->get()) > 0) {
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
        return $this->errorResponse('Usuario no autorizado');
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
        return $this->errorMessage('Usuario no autorizado');
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