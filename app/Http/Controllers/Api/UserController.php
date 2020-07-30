<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\DataCar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Eucomb\User as EucombUser;

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
                return $this->successMessage('user', $data);
                break;
            case 'despachador':
                $data = $this->getDataUser($user);
                return $this->successMessage('user', $data);
                break;
            default:
                return $this->errorMessage('Usuario no autorizado');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        switch (($user = Auth::user())->roles[0]->name) {
            case 'usuario':
                // Registrando la informacion basica del cliente
                $client = $this->setDataUser($user->id, $request);
                $client->sex = $request->sex;
                //registrando el correo
                if ($request->email != $user->email) {
                    if (!($request->email == $user->email) && !(EucombUser::where("email", $request->email)->exists()) && !(User::where('email', $request->email)->exists())) {
                        $client->email = $request->email;
                    } else {
                        return $this->errorMessage('La dirección de correo ya existe');
                    }
                }
                // Registrando el cumpleaños
                $birthdate = Client::find($user->client->id);
                $birthdate->birthdate = $request->birthdate;
                $birthdate->save();
                // Registrando las datos del carro
                if ($request->number_plate != "" || $request->type_car != "") {
                    if (($dataCar = DataCar::find($user->client->id)) == "") {
                        $car = new DataCar();
                        $car->client_id = $user->client->id;
                        $car->number_plate = $request->number_plate;
                        $car->type_car = $request->type_car;
                        $car->save();
                    } else {
                        $dataCar->number_plate = $request->number_plate;
                        $dataCar->type_car = $request->type_car;
                        $dataCar->save();
                    }
                }
                // Registrando la contraseña
                if ($request->password != "") {
                    $client->password = bcrypt($request->password);
                    $client->save();
                    $this->logout(JWTAuth::getToken());
                    return $this->successMessage('message', 'Datos actualizados correctamente, inicie sesion de nuevo');
                }
                $client->save();
                break;
            case 'despachador':
                $dispatcher = $this->setDataUser($user->id, $request);
                $dispatcher->save();
                break;
            default:
                return $this->errorMessage('Usuario no autorizado');
        }
        return $this->successMessage('message', 'Datos actualizados correctamente');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
    // Metodo para establecer la informacion basica de un usuario
    private function setDataUser($id, $request)
    {
        $user = User::find($id);
        $user->name = $request->name;
        $user->first_surname = $request->first_surname;
        $user->second_surname = $request->second_surname;
        $user->phone = $request->phone;
        $user->address = $request->address;
        return $user;
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->successMessage('message','Cierre de sesion correcto');
        } catch (Exception $e) {
            return $this->errorMessage('Token invalido');
        }
    }
    // Funcion mensaje correcto
    private function successMessage($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
    // Funcion mensaje de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
