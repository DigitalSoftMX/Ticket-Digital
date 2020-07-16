<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Dispatcher;
use App\Eucomb\User as EucombUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Metodo para iniciar sesion en Ticket Digital
    public function login(Request $request)
    {
        // Pregunta si el usuario existe en la BD de Ticket Digital
        $userTicket = User::where('email', $request->email)->first();
        if ($userTicket == null) {
            // Pregunta si el usuario existe en la BD de Eucomb
            $userEucomb = EucombUser::where('email', $request->email)->first();
            if ($userEucomb != null) {
                if ($userEucomb->roles[0]->name == 'usuario') {
                    // Copiando los datos del usuario de BD Eucomb a BD Ticket Digital
                    $user = $this->registerUser($userEucomb);
                    // Obteniendo los apellidos de los usuarios
                    $surnames = explode(" ", $userEucomb->last_name);
                    switch (count($surnames)) {
                        case 1:
                            $user->first_surname = $surnames[0];
                            break;
                        case 2:
                            $user->first_surname = $surnames[0];
                            $user->second_surname = $surnames[1];
                            break;
                        default:
                            $user->first_surname = "";
                            $user->second_surname = "";
                            break;
                    }
                    $user->save();
                    $this->registerClient($userEucomb, $user->id);
                    $user->roles()->attach(Role::where('name', $userEucomb->roles[0]->name)->first());
                    return $this->getResponse($request, $user);
                } else {
                    return $this->errorMessage('Usuario no autorizado');
                }
            } else {
                return $this->errorMessage('El usuario no existe');
            }
        } else {
            if ($userTicket->roles[0]->name == 'usuario' || $userTicket->roles[0]->name == 'despachador') {
                return $this->getResponse($request, $userTicket);
            } else {
                return $this->errorMessage('Usuario no autorizado');
            }
        }
    }
    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        if (!(EucombUser::where("email", $request->email)->exists()) && !(User::where('email', $request->email)->exists())) {
            $user = $this->registerUser($request);
            $user->first_surname = $request->first_surname;
            $user->second_surname = $request->second_surname;
            $user->password = Hash::make($request->password);
            $user->save();
            // Membresia aleatoria no repetible en las dos BD's
            while (true) {
                $membership = rand(10000000, 99999999);
                $request->username = 'G-' . $membership;
                if (!(Client::where('membership', $request->username)->exists()) && !(EucombUser::where('username', $membership)->exists())) {
                    break;
                }
            }
            $this->registerClient($request, $user->id);
            $user->roles()->attach(Role::where('name', 'usuario')->first());
            return $this->getResponse($request, $user);
        } else {
            return $this->errorMessage('El usuario ya existe');
        }
    }
    // Registrando a un usuario
    private function registerUser($data)
    {
        $user = new User();
        $user->name = $data->name;
        $user->password = $data->password;
        $user->sex = $data->sex;
        $user->phone = $data->phone;
        $user->email = $data->email;
        $user->active = '1';
        $user->created_at = now();
        $user->updated_at = now();
        return $user;
    }
    // Registrando a un usuario tipo cliente
    private function registerClient($data, $id)
    {
        $client = new Client();
        $client->user_id = $id;
        $client->membership = $data->username;
        $client->current_balance = 0;
        $client->shared_balance = 0;
        $client->points = 0;
        // Verificar la imagen qr del cliente
        $client->image_qr = $data->username;
        $client->birthdate = $data->birthdate;
        $client->created_at = now();
        $client->updated_at = now();
        $client->save();
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));
            return response()->json([
                'ok' => true,
                'message' => 'Logout success'
            ]);
        } catch (Exception $e) {
            return $this->errorMessage('Token invalido');
        }
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getResponse($request, $user)
    {
        $creds = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($creds)) {
            return $this->errorMessage('Datos incorrectos');
        }
        $user->remember_token = $token;
        $user->save();
        return response()->json([
            'ok' => true,
            'token' => $token
        ]);
    }
    // Metodo mensaje de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
