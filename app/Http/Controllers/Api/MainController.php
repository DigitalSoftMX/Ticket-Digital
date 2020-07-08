<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;

class MainController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function main()
    {
        $user = Auth::user();
        $user->client;
        return response()->json([
            'ok' => true,
            'user' => $user
        ]);
    }
    // Funcion principal para la ventana de abonos
    public function mainBalance()
    {
        return response()->json([
            'ok' => true,
            'stations' => Station::all()
        ]);
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        $user = Auth::user()->client;
        // Comprobar que el saldo sea un multiplo de 100 y mayor a cero
        if ($request->deposit % 100 == 0 && $request->deposit > 0) {
            // Obteniendo el archivo de imagen de pago
            $file = $request->file('image');
            // Validando que el usuario subio un archivo
            if ($file != NULL) {
                // Validando que el archivo es de tipo imagen
                if ((strpos($file->getClientMimeType(), 'image')) === false) {
                    return $this->errorMessage('El archivo no es una imagen');
                } else {
                    //obtenemos el nombre del archivo
                    // $nombre = $file->getClientOriginalName();        
                    $station = UserHistoryDeposit::where([['client_id', $user->id], ['station_id', $request->id_station]])->first();
                    if ($station != null) {
                        $station->balance += $request->deposit;
                        $station->status = 1;
                        $station->save();
                        // Guardando historial de transaccion
                        $this->saveHistoryBalance($station, 'balance', $request->deposit);
                    } else {
                        // Registrando en un historial los abonos del cliente
                        $history = new UserHistoryDeposit();
                        $history->client_id = $user->id;
                        $history->balance = $request->deposit;
                        // Falta guardar la imagen de pago
                        $history->station_id = $request->id_station;
                        $history->status = 1;
                        $history->save();
                        // Guardando historial de transaccion
                        $this->saveHistoryBalance($history, 'balance', 0);
                    }
                    // Actualizando el saldo del usuario
                    $user->current_balance += $request->deposit;
                    $user->update();
                    return $this->successBalance();
                }
            } else {
                return $this->errorMessage('Debe subir su comprobante');
            }
        } else {
            return $this->errorMessage('La cantidad debe ser multiplo de $100');
        }
    }
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        $payments = UserHistoryDeposit::where('client_id', Auth::user()->client->id)->get();
        if (count($payments) != 0) {
            $deposits = array();
            // Obteniendo los abonos del usuario y las estaciones correspondientes
            foreach ($payments as $payment) {
                $payment->station;
                array_push($deposits, $payment);
            }
            return response()->json([
                'ok' => true,
                'payments' => $deposits
            ]);
        } else {
            return $this->errorMessage('No hay abonos realizados');
        }
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        // Verificando que la membresia no le pertenezca al mismo cliente
        if (Auth::user()->client->membership != $request->membership) {
            $contact = Client::where('membership', $request->membership)->first();
            // Si existe el usuario, devuelve la informacion del mismo a quien lo busca
            if ($contact != null) {
                $contact->user;
                return response()->json([
                    'ok' => true,
                    'contact' => $contact,
                ]);
            } else {
                return $this->errorMessage('Usuario no registrado en la aplicacion');
            }
        } else {
            return $this->errorMessage('Membresia del cliente');
        }
    }
    // Funcion para obtener los contactos de un usuario
    public function getContact()
    {
        $contacts = Contact::where('transmitter_id', Auth::user()->client->id)->get();
        if (count($contacts) != 0) {
            $listContacts = array();
            foreach ($contacts as $contact) {
                $contact->receiver;
                $contact->receiver->user;
                array_push($listContacts, $contact);
            }
            return response()->json([
                'ok' => true,
                'contacts' => $listContacts
            ]);
        } else {
            return response()->json([
                'ok' => true,
                'message' => 'No tienes contactos agregados'
            ]);
        }
    }
    // Funcion para agregar un contacto a un usuario
    public function addContact(Request $request)
    {
        if (!(Contact::where([['transmitter_id', Auth::user()->client->id], ['receiver_id', $request->id_contact]])->exists())) {
            $contact = new Contact;
            $contact->transmitter_id = Auth::user()->client->id;
            $contact->receiver_id = $request->id_contact;
            $contact->save();
            return response()->json([
                'ok' => true,
                'message' => 'Contacto agregado correctamente'
            ]);
        } else {
            return $this->errorMessage('El usuario ya ha sido agregado anteriormente');
        }
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if ($request->balance % 100 == 0 && $request->balance > 0) {
            // Obteniendo el saldo disponible en la estacion correspondiente
            $user = Auth::user()->client;
            $payment = UserHistoryDeposit::find($request->id_payment);
            if ($payment != null && $payment->client_id == $user->id) {
                if (!($request->balance > $payment->balance)) {
                    $receivedBalance = SharedBalance::where([['transmitter_id', $user->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id]])->first();
                    if ($receivedBalance != null) {
                        $receivedBalance->balance += $request->balance;
                        $receivedBalance->status = 1;
                        $receivedBalance->save();
                        // Guardando historial de transaccion
                        $this->saveHistoryBalance($receivedBalance, 'share', $request->balance);
                    } else {
                        $sharedBalance = new SharedBalance();
                        $sharedBalance->transmitter_id = Auth::user()->client->id;
                        $sharedBalance->receiver_id = $request->id_contact;
                        $sharedBalance->balance = $request->balance;
                        $sharedBalance->station_id = $payment->station_id;
                        $sharedBalance->status = 1;
                        $sharedBalance->save();
                        // Guardando historial de transaccion
                        $this->saveHistoryBalance($sharedBalance, 'share', 0);
                    }
                    $payment->balance -= $request->balance;
                    $payment->save();
                    // Actualizando el abono total del cliente emisor
                    $user->current_balance -= $request->balance;
                    $user->save();
                    // Acutalizando el abono compartido del cliente receptor
                    $receiverUser = Client::find($request->id_contact);
                    $receiverUser->shared_balance += $request->balance;
                    $receiverUser->save();
                    return $this->successBalance();
                } else {
                    return $this->errorMessage('El deposito es mayor al disponible');
                }
            } else {
                return $this->errorMessage('El deposito no corresponde al usuario');
            }
        } else {
            return $this->errorMessage('La cantidad debe ser multiplo de $100');
        }
    }
    // Funcion que busca los abonos recibidos hacia el usuario por parte de otros clientes
    public function listReceivedPayments()
    {
        $balances = SharedBalance::where('receiver_id', Auth::user()->client->id)->get();
        if (count($balances) != 0) {
            $receivedBalances = array();
            foreach ($balances as $balance) {
                $balance->receiver;
                $balance->station;
                $balance->transmitter->user;
                array_push($receivedBalances, $balance);
            }
            return response()->json([
                'ok' => true,
                'payments' => $receivedBalances
            ]);
        } else {
            return $this->errorMessage('No hay abonos realizados');
        }
    }
    // Funcion para devolver la membresía del cliente y la estacion
    public function useBalance(Request $request)
    {
        $payment = UserHistoryDeposit::find($request->id_payment);
        if ($payment != null && $payment->client_id == Auth::user()->client->id) {
            return response()->json([
                'ok' => true,
                'membership' => Auth::user()->client->membership,
                'station' => $payment->station
            ]);
        } else {
            return $this->errorMessage('No hay abono en la cuenta');
        }
    }
    // Funcion para devolver informacion de un saldo compartido
    public function useSharedBalance(Request $request)
    {
        $payment = SharedBalance::find($request->id_payment);
        if ($payment != null && $payment->receiver_id == Auth::user()->client->id) {
            return response()->json([
                'ok' => true,
                'tr_membership' => $payment->transmitter->membership,
                'membership' => $payment->receiver->membership,
                'station' => $payment->station
            ]);
        } else {
            return $this->errorMessage('No hay abono en la cuenta');
        }
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        $historyBalances = History::where([['client_id', Auth::user()->client->id], ['type', $request->type]])->get();
        if (count($historyBalances) > 0) {
            $balances = array();
            switch ($request->type) {
                case 'balance':
                    foreach ($historyBalances as $historyBalance) {
                        $balance = json_decode($historyBalance->action);
                        $station = Station::find($balance->station_id);
                        $action = array('balance' => $balance->balance, 'station' => $station->name, 'date' => $historyBalance->created_at);
                        array_push($balances, $action);
                    }
                    return $this->historyBalance($balances);
                    break;
                case 'share':
                    foreach ($historyBalances as $historyBalance) {
                        $balance = json_decode($historyBalance->action);
                        $station = Station::find($balance->station_id);
                        $receiver = Client::find($balance->receiver_id);
                        $action = array('station' => $station->name, 'balance' => $balance->balance, 'membership' => $receiver->membership, 'name' => $receiver->user->name, 'date' => $historyBalance->created_at);
                        array_push($balances, $action);
                    }
                    return $this->historyBalance($balances);
                    break;
            }
        } else {
            return $this->errorMessage('No se ha realizado abonos a su cuenta');
        }
    }
    // Funcion para guardar historial de abonos a la cuenta del cliente
    private function saveHistoryBalance($history, $type, $balance)
    {
        $historyBalance = new History();
        if ($balance != 0) {
            $history->balance = $balance;
        }
        switch ($type) {
            case 'balance':
                $historyBalance->client_id = $history->client_id;
                break;
            case 'share':
                $historyBalance->client_id = $history->transmitter_id;
                break;
        }
        $historyBalance->action = $history;
        $historyBalance->type = $type;
        $historyBalance->save();
    }
    // Funcion mensajes de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje de exito para el abono de una cuenta
    private function successBalance()
    {
        return response()->json([
            'ok' => true,
            'message' => 'Abono realizado correctamente'
        ]);
    }
    // Funcion mensaje json para historial
    private function historyBalance($balances)
    {
        return response()->json([
            'ok' => true,
            'balances' => $balances
        ]);
    }
}
