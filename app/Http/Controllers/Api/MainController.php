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

    public function pay(Request $request)
    {
        // Comprobar que el saldo sea un multiplo de 100
        if ($request->deposit % 100 != 0 || $request->deposit <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'La cantidad debe ser multiplo de $100'
            ]);
        }
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
}
