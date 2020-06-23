<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function abonar(Request $request)
    {
        $user = User::find($request->id);
        // Verifica si el usuario es el correcto
        if (Auth::user()->id != $request->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Acceso no autorizado'
            ]);
        }
        // Actualizando el saldo del usuario
        $user->client->current_balance += $request->balance;
        $user->client->update();
        return response()->json([
            'ok' => true,
            'message' => 'Salgo agregado correctamente'
        ]);
    }
}
