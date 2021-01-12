<?php

namespace App\Http\Controllers\Api;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\User;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ContactController extends Controller
{
    // Funcion para obtener los contactos de un usuario
    public function getListContacts()
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (count($contacts = $user->client->contacts) > 0) {
                $listContacts = array();
                foreach ($contacts as $contact) {
                    $data['id'] = $contact->receiver->id;
                    $data['receiver']['membership'] = $contact->receiver->user->username; 
                    $data['receiver']['user']['name'] = $contact->receiver->user->name;
                    $data['receiver']['user']['first_surname'] = $contact->receiver->user->first_surname;
                    $data['receiver']['user']['second_surname'] = $contact->receiver->user->second_surname;
                    array_push($listContacts, $data);
                }
                return $this->successResponse('contacts', $listContacts);
            }
            return $this->errorResponse('No tienes contactos agregados');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (($contact = User::where([['username', $request->membership], ['username', '!=', $user->username]])->first()) != null) {
                if ($contact->roles[0]->id == 5) {
                    $userTicket['id'] = $contact->client->id;
                    $userTicket['membership'] = $contact->username;
                    $userTicket['user']['name'] = $contact->name;
                    $userTicket['user']['first_surname'] = $contact->first_surname;
                    $userTicket['user']['second_surname'] = $contact->second_surname;
                    return $this->successResponse('contact', $userTicket);
                }
            }
            return $this->errorResponse('Membresía de usuario no disponible');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para agregar un contacto a un contacto
    public function addContact(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (!($user->client->contacts->where('receiver_id', $request->id_contact)->first())) {
                $contact = new Contact;
                $contact->create($request->merge(['transmitter_id' => $user->client->id, 'receiver_id' => $request->id_contact])->all());
                return $this->successResponse('message', 'Contacto agregado correctamente');
            }
            return $this->errorResponse('El usuario ya ha sido agregado anteriormente');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Funcion para eliminar a un contacto
    public function deleteContact(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            if (($contact = $user->client->contacts->where('receiver_id', $request->id_contact)->first()) != null) {
                $contact->delete();
                return $this->successResponse('message', 'Contacto eliminado correctamente');
            }
            return $this->errorResponse('El contacto no existe');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->errorResponse('Token invalido');
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
    // Funcion mensajes de error
    private function errorResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
