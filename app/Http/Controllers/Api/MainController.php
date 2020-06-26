<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
        try {
            return response()->json([
                'ok' => true,
                'user' => Auth::user(),
                'data' => Auth::user()->client
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => '' . $e
            ]);
        }
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
                    // Verifica si el usuario es el correcto
                    if (Auth::user()->id == $request->id_user) {
                        // Comprobando si el deposito es a la misma estacion
                        $user = User::find($request->id_user);
                        $station = UserHistoryDeposit::where('station_id', '=', $request->id_station)->first();
                        if ($station != null) {
                            $station->balance += $request->deposit;
                            $station->save();
                        } else {
                            // Registrando en un historial los abonos del cliente
                            $history = new UserHistoryDeposit();
                            $history->client_id = $user->client->id;
                            $history->balance = $request->deposit;
                            // Falta guardar la imagen de pago
                            $history->station_id = $request->id_station;
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
        // Buscando la membresia del contacto en la BD de Ticket Digital para enviar saldo
        if (Auth::user()->client->membership != $request->membership) {
            $contact = Client::where('membership', '=', $request->membership)->first();
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
        $listContacts=array();
        foreach($contacts as $contact){
            $client=Client::find($contact->receiver_id);
            $user=User::find($client->user_id);
            array_push($listContacts,[$user,$client]);
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
}
