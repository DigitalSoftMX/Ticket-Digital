<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\User;
use App\UserHistoryDeposit;
use Exception;
use Illuminate\Support\Facades\Auth;

class MainController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function main()
    {
        $user = User::find(Auth::user()->id);
        $user->client;
        $data = array($user);
        return response()->json([
            'ok' => true,
            'user' => $data[0]
        ]);
    }
    // Funcion principal para la ventana de abonos
    public function mainBalance()
    {
        return response()->json([
            'stations' => Station::all()
        ]);
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
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
                    // Se puede ahorrar la linea siguiente por medio de Auth::user()->client->
                    $user = User::find(Auth::user()->id);
                    $station = UserHistoryDeposit::where([['client_id', $user->client->id], ['station_id', $request->id_station]])->first();
                    if ($station != null) {
                        $station->balance += $request->deposit;
                        $station->status = 1;
                        $station->save();
                    } else {
                        // Registrando en un historial los abonos del cliente
                        $history = new UserHistoryDeposit();
                        $history->client_id = $user->client->id;
                        $history->balance = $request->deposit;
                        // Falta guardar la imagen de pago
                        $history->station_id = $request->id_station;
                        $history->status = 1;
                        $history->save();
                    }
                    // Actualizando el saldo del usuario
                    $user->client->current_balance += $request->deposit;
                    $user->client->update();
                    return response()->json([
                        'ok' => true,
                        'message' => 'Saldo agregado correctamente'
                    ]);
                }
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Debe subir su comprobante'
                ]);
            }
        } else {
            return response()->json([
                'ok' => false,
                'message' => 'La cantidad debe ser multiplo de $100'
            ]);
        }
    }
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function listPersonalPayments()
    {
        try {
            $payments = UserHistoryDeposit::all()->where('client_id', '=', Auth::user()->client->id);
            if (count($payments) != 0) {
                $deposits = array();
                // Obteniendo los abonos del usuario y las estaciones correspondientes
                foreach ($payments as $payment) {
                    array_push($deposits, $payment, $payment->station);
                }
                return response()->json([
                    'ok' => true,
                    'status_payment' => true,
                    'payments' => $deposits
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'status_payment' => false,
                    'message' => 'No hay abonos realizados'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'status_payment' => false,
                'message' => '' . $e
            ]);
        }
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        // Verificando que la membresia no le pertenezca al mismo cliente
        if (Auth::user()->client->membership != $request->membership) {
            $contact = Client::where('membership', '=', $request->membership)->first();
            // Si existe el usuario, devuelve la informacion del mismo a quien lo busca
            if ($contact != null) {
                return response()->json([
                    'ok' => true,
                    'contact' => User::find($contact->user_id),
                    'data' => $contact
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
        $listContacts = array();
        foreach ($contacts as $contact) {
            $client = Client::find($contact->receiver_id);
            $user = User::find($client->user_id);
            array_push($listContacts, [$user, $client]);
        }
        return response()->json([
            'ok' => true,
            'contacts' => $listContacts
        ]);
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
        // Posible bug, arreglar, potencial codigo corrupto en el envio de saldo
        if ($request->balance % 100 == 0 && $request->balance > 0) {
            // $payment = UserHistoryDeposit::where([['client_id', Auth::user()->client->id], ['station_id', $request->id_station]])->first();
            // Obteniendo el saldo disponible en la estacion correspondiente
            $payment = UserHistoryDeposit::find($request->id_payment);
            if (!($request->balance > $payment->balance)) {
                $receivedBalance = SharedBalance::where([['transmitter_id', Auth::user()->client->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id]])->first();
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
                return response()->json([
                    'ok' => true,
                    'message' => 'Saldo enviado correctamente'
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'El deposito es mayor al disponible'
                ]);
            }
        } else {
            return response()->json([
                'ok' => false,
                'message' => 'La cantidad debe ser multiplo de $100'
            ]);
        }
    }
    // Funcion que busca los abonos recibidos hacia el usuario por parte de otros clientes
    public function listReceivedPayments()
    {
        $balances = SharedBalance::where('receiver_id', Auth::user()->client->id)->get();
        if (count($balances) != 0) {
            $receivedBalances = array();
            foreach ($balances as $balance) {
                $receiver = $balance->receiver;
                $station = $balance->station;
                $transmitter = $balance->transmitter->user;
                array_push($receivedBalances, $balance);
            }
            return response()->json([
                'ok' => true,
                'status_payment' => true,
                'payments' => $receivedBalances
            ]);
        } else {
            return response()->json([
                'ok' => false,
                'status_payment' => false,
                'message' => 'No hay abonos realizados'
            ]);
        }
    }
    // Funcion para solicitar saldo a un usuario desde la aplicacion
    public function requestBalance(Request $request)
    {
        // Pendiente
    }
    // Crear una funcion para verificar si la cantidad a depositar o enviar es multiplo de 100
    // Crear una funcion para el error del punto anterior
}
