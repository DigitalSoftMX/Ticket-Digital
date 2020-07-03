<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
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
                    return response()->json([
                        'ok' => false,
                        'message' => 'El archivo no es una imagen'
                    ]);
                } else {
                    //obtenemos el nombre del archivo
                    // $nombre = $file->getClientOriginalName();        
                    $station = UserHistoryDeposit::where([['client_id', $user->id], ['station_id', $request->id_station]])->first();
                    if ($station != null) {
                        $station->balance += $request->deposit;
                        $station->status = 1;
                        $station->save();
                    } else {
                        // Registrando en un historial los abonos del cliente
                        $history = new UserHistoryDeposit();
                        $history->client_id = $user->id;
                        $history->balance = $request->deposit;
                        // Falta guardar la imagen de pago
                        $history->station_id = $request->id_station;
                        $history->status = 1;
                        $history->save();
                    }
                    // Actualizando el saldo del usuario
                    $user->current_balance += $request->deposit;
                    $user->update();
                    return $this->successBalance();
                }
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Debe subir su comprobante'
                ]);
            }
        } else {
            return $this->responseErrorOneHundred();
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
                'status_payment' => true,
                'payments' => $deposits
            ]);
        } else {
            return $this->thereAreNoBalances();
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
                return response()->json([
                    'ok' => false,
                    'message' => 'Usuario no registrado en la aplicacion'
                ]);
            }
        } else {
            return response()->json([
                'ok' => false,
                'message' => 'Membresia del cliente'
            ]);
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
            return response()->json([
                'ok' => false,
                'message' => 'El usuario ya ha sido agregado anteriormente'
            ]);
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
                    } else {
                        $sharedBalance = new SharedBalance();
                        $sharedBalance->transmitter_id = Auth::user()->client->id;
                        $sharedBalance->receiver_id = $request->id_contact;
                        $sharedBalance->balance = $request->balance;
                        $sharedBalance->station_id = $payment->station_id;
                        $sharedBalance->status = 1;
                        $sharedBalance->save();
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
                    return response()->json([
                        'ok' => false,
                        'message' => 'El deposito es mayor al disponible'
                    ]);
                }
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'El deposito no corresponde al usuario'
                ]);
            }
        } else {
            return $this->responseErrorOneHundred();
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
                'status_payment' => true,
                'payments' => $receivedBalances
            ]);
        } else {
            return $this->thereAreNoBalances();
        }
    }
    // Funcion para solicitar saldo a un usuario desde la aplicacion
    public function requestBalance(Request $request)
    {
        // Pendiente
    }
    // Funcion mensaje de error para el saldo no multiplo de 100 o negativo
    private function responseErrorOneHundred()
    {
        return response()->json([
            'ok' => false,
            'message' => 'La cantidad debe ser multiplo de $100'
        ]);
    }
    // Funcion mensaje de error no hay abonos realizados
    private function thereAreNoBalances()
    {
        return response()->json([
            'ok' => false,
            'status_payment' => false,
            'message' => 'No hay abonos realizados'
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
}
