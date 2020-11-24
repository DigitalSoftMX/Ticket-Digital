<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DataCar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Station;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Metodo para inicar sesion
    public function login(Request $request)
    {
        switch (count($user = User::where('email', $request->email)->get())) {
            case 0:
                return $this->errorResponse('El usuario no existe');
            case 1:
                if ($user[0]->roles[0]->name == 'usuario' || $user[0]->roles[0]->name == 'despachador') {
                    return $this->getToken($request, $user[0]);
                }
                return $this->errorResponse('Usuario no autorizado');
            default:
                return $this->errorResponse('Debe cambiar su correo desde la aplicación Eucomb');
        }
    }
    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'email' => [
                'required', 'email', Rule::unique((new User)->getTable())
            ],
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }
        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'E-' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
            if (!(User::where('username', $membership)->exists())) {
                break;
            }
        }
        $password = $request->password;
        $request->merge(['username' => $membership, 'active' => 1, 'password' => Hash::make($request->password)]);
        $user = new User();
        $user = $user->create($request->all());
        $request->merge(['user_id' => $user->id, 'current_balance' => 0, 'shared_balance' => 0, 'points' => 0, 'image' => $membership]);
        $client = new Client();
        $client->create($request->all());
        $user->roles()->attach('5');
        if ($request->number_plate != "" || $request->type_car != "") {
            $request->merge(['client_id' => $user->client->id]);
            $car = new DataCar();
            $car->create($request->only(['client_id', 'number_plate', 'type_car']));
        }
        Storage::disk('public')->deleteDirectory($user->username);
        $request->merge(['password' => $password]);
        return $this->getToken($request, $user);
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Datos incorrectos');
        }
        $user->update(['remember_token' => $token]);
        if ($user->roles[0]->name == 'usuario') {
            $user->client->update($request->only('ids'));
        }
        return $this->successReponse('token', $token);
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));
            return $this->successReponse('message', 'Cierre de sesión correcto');
        } catch (Exception $e) {
            return $this->errorResponse('Token inválido');
        }
    }
    // Metodo para actualizar la ip de una estacion
    public function uploadIPStation($station_id, Request $request)
    {
        $station = Station::where('number_station', $station_id)->first();
        $station->update($request->only('ip'));
        return "Dirección IP actualizado correctamente";
    }
    // Funcion mensaje correcto
    private function successReponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Metodo mensaje de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Precios de gasolina para wordpress, no se incluye en el proyecto Ticket
    public function price(Request $request)
    {
        if ($request->place != null && $request->type != null) {
            $prices = new SimpleXMLElement('https://publicacionexterna.azurewebsites.net/publicaciones/prices', NULL, TRUE);
            $precio = '--';
            foreach ($prices->place as $place) {
                if ($place['place_id'] == $request->place) {
                    foreach ($place->gas_price as $price) {
                        if ($price['type'] == $request->type) {
                            $precio = (float) $price;
                            return $precio;
                        }
                    }
                }
            }
            return $precio;
        } else {
            return 'Falta el lugar o el tipo de gasolina';
        }
    }
}
