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
    public function main(Request $request){
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
}
