<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DataCar;
use App\Eucomb\User as EucombUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Exception;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Metodo para inicar sesion
    public function login(Request $request)
    {
        if (($userTicket = User::where('email', $request->email)->first()) == null) {
            if (($userEucomb = EucombUser::where('email', $request->email)->first()) != null) {
                if (($role = $userEucomb->roles[0]->name) == 'usuario') {
                    try {
                        $user = $this->registerUser($userEucomb);
                        // Obteniendo los apellidos del usuario
                        switch (count($surnames = explode(" ", $userEucomb->last_name))) {
                            case 1:
                                $user->first_surname = $surnames[0];
                                break;
                            case 2:
                                $user->first_surname = $surnames[0];
                                $user->second_surname = $surnames[1];
                                break;
                        }
                        $user->save();
                    } catch (Exception $e) {
                        return $this->errorMessage('Su correo esta repetido con otro usuario en Eucomb, intente cambiarlo e iniciar sesion nuevamente');
                    }
                    $this->registerClient($userEucomb, $user->id);
                    $user->roles()->attach(Role::where('name', $role)->first());
                    Storage::disk('public')->deleteDirectory($user->client->membership);
                    return $this->getToken($request, $user);
                }
                return $this->errorMessage('Usuario no autorizado');
            }
            return $this->errorMessage('El usuario no existe');
        }
        if ($userTicket->roles[0]->name == 'usuario' || $userTicket->roles[0]->name == 'despachador') {
            return $this->getToken($request, $userTicket);
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        if (!(EucombUser::where("email", $request->email)->exists()) && !(User::where('email', $request->email)->exists())) {
            $user = $this->registerUser($request);
            $user->first_surname = $request->first_surname;
            $user->second_surname = $request->second_surname;
            $user->password = bcrypt($request->password);
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
            if ($request->number_plate != "" || $request->type_car != "") {
                $dataCar = new DataCar();
                $dataCar->client_id = $user->client->id;
                $dataCar->number_plate = $request->number_plate;
                $dataCar->type_car = $request->type_car;
                $dataCar->save();
            }
            Storage::disk('public')->deleteDirectory($user->client->membership);
            return $this->getToken($request, $user);
        }
        return $this->errorMessage('El usuario ya existe');
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
        $user->address = $data->address;
        $user->active = '1';
        return $user;
    }
    // Registrando a un usuario tipo cliente
    private function registerClient($data, $id)
    {
        $client = new Client();
        $client->user_id = $id;
        $client->current_balance = 0;
        $client->shared_balance = 0;
        $client->points = 0;
        $client->birthdate = $data->birthdate;
        $client->save();
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));
            return $this->successMessage('message', 'Cierre de sesion correcto');
        } catch (Exception $e) {
            return $this->errorMessage('Token invalido');
        }
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user)
    {
        $creds = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($creds)) {
            return $this->errorMessage('Datos incorrectos');
        }
        $user->remember_token = $token;
        $user->save();
        if ($user->roles[0]->name == 'usuario') {
            $user->client->ids = $request->ids;
            $user->client->update();
        }
        return $this->successMessage('token', $token);
    }
    // Funcion mensaje correcto
    private function successMessage($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
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
