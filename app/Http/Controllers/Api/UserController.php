<?php

namespace App\Http\Controllers\Api;

use App\DataCar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                $data = $this->getDataUser($user);
                $data['email'] = $user->email;
                $data['sex'] = $user->sex;
                $data['birthdate'] = $user->client->birthdate;
                if (($car = $user->client->car) != "") {
                    $dataCar = array('number_plate' => $car->number_plate, 'type_car' => $car->type_car);
                } else {
                    $dataCar = array('number_plate' => '', 'type_car' => '');
                }
                $data['data_car'] = $dataCar;
                return $this->successResponse('user', $data);
            case 'despachador':
                $data = $this->getDataUser($user);
                return $this->successResponse('user', $data);
            default:
                return $this->logout(JWTAuth::getToken());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string|min:10|max:10',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }
        switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                // Registrando la informacion basica del cliente
                $user->update($request->only('name', 'first_surname', 'second_surname', 'phone', 'address', 'sex'));
                //registrando el correo
                if ($request->email != $user->email) {
                    if (!(User::where('email', $request->email)->exists())) {
                        $user->update($request->only('email'));
                    } else {
                        return $this->errorResponse('La dirección de correo ya existe');
                    }
                }
                $user->client->update($request->only('birthdate'));
                // Registrando las datos del carro
                if ($request->number_plate != "" || $request->type_car != "") {
                    if ($user->client->car == null) {
                        $request->merge(['client_id' => $user->client->id]);
                        $car = new DataCar();
                        $car->create($request->only('client_id', 'number_plate', 'type_car'));
                    } else {
                        $user->client->car->update($request->only('number_plate', 'type_car'));
                    }
                }
                // Registrando la contraseña
                if ($request->password != "") {
                    $user->update(['password' => bcrypt($request->password)]);
                    $this->logout(JWTAuth::getToken());
                    return $this->successResponse('message', 'Datos actualizados correctamente, inicie sesión de nuevo');
                }
                break;
            case 'despachador':
                $user->update($request->only('name', 'first_surname', 'second_surname', 'phone', 'address'));
                break;
            default:
                return $this->logout(JWTAuth::getToken());
        }
        return $this->successResponse('message', 'Datos actualizados correctamente');
    }

    // Funcion para obtener la informacion basica de un usuario
    private function getDataUser($user)
    {
        $data = array(
            'id' => $user->id,
            'name' => $user->name,
            'first_surname' => $user->first_surname,
            'second_surname' => $user->second_surname,
            'phone' => $user->phone,
            'address' => $user->address
        );
        return $data;
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->successResponse('message', 'Cierre de sesion correcto');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    // Funcion mensaje correcto
    private function successResponse($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Funcion mensaje de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
