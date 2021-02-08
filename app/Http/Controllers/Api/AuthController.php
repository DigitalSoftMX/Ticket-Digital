<?php

namespace App\Http\Controllers\Api;

use App\Canje;
use App\Client;
use App\DataCar;
use App\Empresa;
use App\Exchange;
use App\Gasoline;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lealtad\Tarjeta;
use App\Lealtad\Ticket;
use App\SalesQr;
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
        if (($u = User::where('username', $request->email)->first()) != null) {
            if ($u->email != null) {
                $user = User::where('email', $u->email)->get();
                $request->merge(['email' => $u->email]);
            } else {
                return $this->errorResponse('No existe un correo electrónico registrado. Ingrese un correo electrónico.', $u->id);
            }
        } else {
            $user = User::where('email', $request->email)->get();
        }
        switch (count($user)) {
            case 0:
                return $this->errorResponse('El usuario no existe', null);
            case 1:
                foreach ($user[0]->roles as $rol) {
                    if ($rol->id == 4 || $rol->id == 5) {
                        $validator = Validator::make($request->only('email'), ['email' => 'email']);
                        if ($validator->fails()) {
                            return $this->errorResponse('Su correo actual no es válido. Ingrese un nuevo correo.', $user[0]->id);
                        }
                        return $this->getToken($request, $user[0], $rol->id);
                    }
                }
                return $this->errorResponse('Usuario no autorizado', null);
            default:
                if ($u != null) {
                    return $this->errorResponse('Correo duplicado. Ingrese un nuevo correo.', $u->id);
                } else {
                    return $this->errorResponse('Intente ingresar con su membresía.', null);
                }
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
            'number_plate' => [Rule::unique((new DataCar())->getTable())],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), null);
        }
        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'E' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
            if (!(User::where('username', $membership)->exists())) {
                break;
            }
        }
        $password = $request->password;
        $request->merge(['username' => $membership, 'active' => 1, 'password' => Hash::make($request->password)]);
        $user = new User();
        $user = $user->create($request->all());
        $request->merge(['user_id' => $user->id, 'current_balance' => 0, 'shared_balance' => 0, 'points' => Empresa::find(1)->points, 'image' => $membership]);
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
        return $this->getToken($request, $user, 5);
    }
    // Método para actualizar solo el correo eletrónico de un usuario
    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'email' => [
                'required', 'email', Rule::unique((new User)->getTable())
            ],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), $request->id);
        }
        $user = User::find($request->id);
        $user->update($request->only('email'));
        return $this->successReponse('email', $request->email);
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));
            return $this->successReponse('message', 'Cierre de sesión correcto');
        } catch (Exception $e) {
            return $this->errorResponse('Token inválido', null);
        }
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user, $rol)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Datos incorrectos', null);
        }
        $user->update(['remember_token' => $token]);
        if ($rol == 5) {
            /* if ($user->client->ids == null) {
                if (count($dataPoints = Tarjeta::where('number_usuario', $user->username)->get()) > 0) {
                    $user->client->update(['points' => $dataPoints->sum('totals'), 'visits' => $dataPoints->sum('visits')]);
                    foreach ($dataPoints as $dataPoint) {
                        $dataPoint->delete();
                    }
                }
                foreach (Ticket::where('number_usuario', $user->username)->get() as $ticket) {
                    if ($ticket->descrip != 'Solo se permiten sumar 80 puntos por día' && $ticket->descrip != 'Pertenece a otro beneficio') {
                        $dataSaleQr = new SalesQr();
                        $dataSaleQr->sale = $ticket->number_ticket;
                        if ($ticket->descrip == 'Información errónea') {
                            $dataSaleQr->gasoline_id = null;
                            $dataSaleQr->points = 0;
                            $dataSaleQr->liters = 0;
                            $dataSaleQr->payment = 0;
                        } else {
                            $dataSaleQr->gasoline_id = Gasoline::where('name', 'LIKE', '%' . $ticket->producto . '%')->first()->id;
                            $dataSaleQr->points = $ticket->punto;
                            $dataSaleQr->liters = $ticket->litro;
                            $dataSaleQr->payment = $ticket->costo;
                        }
                        $dataSaleQr->station_id = $ticket->id_gas;
                        $dataSaleQr->client_id = $user->client->id;
                        $dataSaleQr->created_at = $ticket->created_at;
                        $dataSaleQr->updated_at = $ticket->updated_at;
                        $dataSaleQr->save();
                    }
                    $ticket->delete();
                }
                foreach (History::where('number_usuario', $user->username)->get() as $history) {
                    $dataHistoryExchange = new Exchange();
                    $dataHistoryExchange->client_id = $user->client->id;
                    $dataHistoryExchange->exchange = $history->numero;
                    $dataHistoryExchange->station_id = $history->id_station;
                    $dataHistoryExchange->points = $history->points;
                    $dataHistoryExchange->value = $history->value;
                    $dataHistoryExchange->status = 14;
                    $dataHistoryExchange->admin_id = $history->id_admin;
                    $dataHistoryExchange->created_at = $history->created_at;
                    $dataHistoryExchange->updated_at = $history->updated_at;
                    $dataHistoryExchange->save();
                    $history->delete();
                }
                foreach (Canje::where('number_usuario', $user->username)->get() as $canje) {
                    if (!(Exchange::where('exchange', $canje->conta)->exists())) {
                        $dataExchange = new Exchange();
                        $dataExchange->client_id = $user->client->id;
                        $dataExchange->exchange = $canje->conta;
                        $dataExchange->station_id = $canje->id_estacion;
                        $dataExchange->points = $canje->punto;
                        $dataExchange->value = $canje->value;
                        $dataExchange->status = $canje->estado + 10;
                        $dataExchange->created_at = $canje->created_at;
                        $dataExchange->updated_at = $canje->updated_at;
                        $dataExchange->save();
                    }
                    $canje->delete();
                }
            } */
            $user->client->update($request->only('ids'));
        }
        return $this->successReponse('token', $token);
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
    private function errorResponse($message, $email)
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
            'id' => $email
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
