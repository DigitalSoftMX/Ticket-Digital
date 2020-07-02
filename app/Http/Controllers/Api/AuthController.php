<?php

namespace App\Http\Controllers\Api;

use App\Client;
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
    /* Metodo login para la base de datos Ticket Digital */
    public function login(Request $request)
    {
        /* Pregunta si el usuario existe en la BD de Ticket Digital */
        if (!(User::where('email', '=', $request->email)->exists())) {
            /* Pregunta si el usuario existe en la BD de Eucomb */
            if ((EucombUser::where('email', '=', $request->email)->exists())) {
                /* Llamando el rol usuario */
                $role = Role::where('name', 'usuario')->first();
                /* Copiando los datos del usuario de BD Eucomb a BD Ticket Digital */
                $userEucomb = EucombUser::where('email', '=', $request->email)->first();
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
                /* Registrando los datos del usuario tipo cliente */
                $this->registerClient($userEucomb, $user->id);
                $user->roles()->attach($role);
                return $this->getResponse($request);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'El usuario no existe'
                ]);
            }
        } else {
            return $this->getResponse($request);
        }
    }

    /* Metodo para registrar a un usuario nuevo */
    public function register(Request $request)
    {
        $userEucomb = EucombUser::where("email", '=', $request->email)->first();
        if ($userEucomb == "") {
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
                return $this->getResponse($request);
            } catch (Exception $e) {
                return response()->json([
                    'ok' => false,
                    'message' => '' . $e
                ]);
            }
        } else {
            return $this->errorResponse();
        }
    }

    /* Registrando a un usuario */
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

    private function registerClient($data, $id)
    {
        $client = new Client();
        $client->user_id = $id;
        /* Esta en duda la membresia */
        $client->membership = $data->image;
        $client->current_balance = 0;
        $client->shared_balance=0;
        $client->points = 0;
        /* Verificar la imagen qr del cliente */
        $client->image_qr = $data->image;
        /* El cumpleaños esta en la tabla usuarios */
        $client->birthdate = $data->birthdate;
        $client->created_at = now();
        $client->updated_at = now();
        $client->save();
        return $client;
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
            return response()->json([
                'ok' => false,
                'message' => '' . $e
            ]);
        }
    }

    /* Metodo para iniciar sesion, delvuelve el token */
    private function getResponse($request)
    {
        $creds = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($creds)) {
            return response()->json([
                'ok' => false,
                'message' => 'Datos incorrectos'
            ]);
        }
        return response()->json([
            'ok' => true,
            'token' => $token
        ]);
    }

    /* Metodo Error response */
    private function errorResponse()
    {
        return response()->json([
            'ok' => false
        ]);
    }

    /* 'user'=>Auth::user() */
}
