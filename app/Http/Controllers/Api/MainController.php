<?php

namespace App\Http\Controllers\Api;

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
    public function pay(Request $request)
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
                    $user = User::find($request->id_user);
                    // Verifica si el usuario es el correcto
                    if (Auth::user()->id != $request->id_user) {
                        return response()->json([
                            'ok' => false,
                            'message' => 'Acceso no autorizado'
                        ]);
                    }
                    // Registrando en un historial los abonos del cliente
                    $history = new UserHistoryDeposit();
                    $history->client_id = $user->client->id;
                    $history->balance = $request->deposit;
                    // Falta guardar la imagen de pago
                    $history->station_id = $request->id_station;
                    $history->save();
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
}
