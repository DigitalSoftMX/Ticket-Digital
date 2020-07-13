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
            /* Pregunta si el usuario existe en la BD de Eucomb */
            $userEucomb = EucombUser::where('email', $request->email)->first();
            if ($userEucomb != null) {
                if ($userEucomb->roles[0]->name == 'usuario' || $userEucomb->roles[0]->name == 'despachador') {
                    /* Copiando los datos del usuario de BD Eucomb a BD Ticket Digital */
                    $user = $this->registerUser($userEucomb);
                    /* Obteniendo los apellidos de los usuarios */
                    $surnames = str_word_count($userEucomb->last_name, 1);
                    $countSurnames = count($surnames);
                    switch ($countSurnames) {
                        case 1:
                            $user->first_surname = $surnames[0];
                            break;
                        case 2:
                            $user->first_surname = $surnames[0];
                            $user->second_surname = $surnames[1];
                            break;
                        default:
                            /* Falta solucion para los que tienes mas de dos apellidos */
                            $user->first_surname = "";
                            $user->second_surname = "";
                            break;
                    }
                    $user->save();
                    $role = Role::where('name', $userEucomb->roles[0]->name)->first();
                    if ($role->name == 'usuario') {
                        $this->registerClient($userEucomb, $user->id);
                        $user->roles()->attach($role);
                        // Enviar id del usuario que se registra
                        return $this->getResponse($request, $user);
                    } else {
                        $dispatcher = new Dispatcher();
                        $dispatcher->user_id = $user->id;
                        $dispatcher->station_id = 1;
                    }
                } else {
                    return $this->errorMessage('Usuario no autorizado');
                }
            } else {
                return $this->errorMessage('El usuario no existe');
            }
        } else {
            return $this->getResponse($request, $userTicket);
        }
    }

    /* Metodo para registrar a un usuario nuevo */
    public function register(Request $request)
    {
        if (!(EucombUser::where("email", $request->email)->exists())) {
            $role = Role::where('name', 'usuario')->first();
            $user = new User();
            try {
                $user->name = $request->name;
                $user->first_surname = $request->first_surname;
                $user->second_surname = $request->second_surname;
                /* $user->username = $request->username; */
                $user->password = Hash::make($request->password);
                $user->sex = $request->sex;
                $user->phone = $request->phone;
                $user->email = $request->email;
                $user->active = '1';
                // $user->birthdate = $request->birthdate;
                $user->remember_token = '';
                $user->email_verified_at = now();
                $user->created_at = now();
                $user->updated_at = now();
                $user->save();
                $user->roles()->attach($role);
                // Enviar el id del usuario recien registrado
                return $this->getResponse($request, $user);
            } catch (Exception $e) {
                return $this->errorMessage('El usuario ya existe');
            }
        } else {
            return $this->errorResponse();
        }
    }
    // Registrando a un usuario
    private function registerUser($data)
    {
        $user = new User();
        $user->name = $data->name;
        /* $user->username = $request->username; */
        $user->password = $data->password;
        $user->sex = $data->sex;
        $user->phone = $data->phone;
        $user->email = $data->email;
        $user->active = '1';
        $user->remember_token = $data->remember_token;
        $user->email_verified_at = now();
        $user->created_at = now();
        $user->updated_at = now();
        return $user;
    }
    // Registrando a un usuario tipo cliente
    private function registerClient($data, $id)
    {
        $client = new Client();
        $client->user_id = $id;
        $client->membership = $data->image;
        $client->current_balance = 0;
        $client->shared_balance = 0;
        $client->points = 0;
        // Verificar la imagen qr del cliente
        $client->image_qr = $data->image;
        $client->birthdate = $data->birthdate;
        $client->created_at = now();
        $client->updated_at = now();
        $client->save();
    }

    /* Metodo para cerrar sesion */
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

    /* Metodo para iniciar sesion, delvuelve el token */
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

    /* Metodo Error response */
    private function errorResponse()
    {
        return response()->json([
            'ok' => false
        ]);
    }
}
