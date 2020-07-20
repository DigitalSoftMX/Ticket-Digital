<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DispatcherController extends Controller
{
    // Funcion principal del despachador
    public function main()
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $user = Auth::user();
            $data = array(
                'id' => $user->id,
                'name' => $user->name,
                'first_surname' => $user->first_surname,
                'second_surname' => $user->second_surname,
                'email' => $user->email,
                'sex' => $user->sex,
                'phone' => $user->phone,
                'dispatcher_id' => $user->dispatcher->dispatcher_id,
            );
            return $this->successMessage('user', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion mensajes de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje correcto
    private function successMessage($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
}
