<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->roles[0]->name == 'despachador') {
            $user = Auth::user();
            $data = array(
                'id' => $user->id,
                'name' => $user->name,
                'first_surname' => $user->first_surname,
                'second_surname' => $user->second_surname,
                'phone' => $user->phone,
            );
            return $this->successMessage('user', $data);
        } else {
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
        if (Auth::user()->roles[0]->name == 'despachador') {
            $user = User::find(Auth::user()->id);
            $user->name = $request->name;
            $user->first_surname = $request->first_surname;
            $user->second_surname = $request->second_surname;
            $user->phone = $request->phone;
            if ($request->password != "") {
                $user->password = bcrypt($request->password);
                $user->save();
                $this->logout(JWTAuth::getToken());
                return $this->successMessage('message', 'Datos actualizados correctamente, inicie sesion de nuevo');
            }
            return $this->successMessage('message', 'Datos actualizados correctamente');
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
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
    // Metodo para cerrar sesion
    public function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return response()->json([
                'ok' => true,
                'message' => 'Logout success'
            ]);
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
