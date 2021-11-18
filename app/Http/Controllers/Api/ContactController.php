<?php

namespace App\Http\Controllers\Api;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\ResponsesAndLogout;
use App\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class ContactController extends Controller
{
    private $user, $client, $response;
    public function __construct(ResponsesAndLogout $response)
    {
        $this->user = auth()->user();
        $this->response = $response;
        $this->user && $this->user->roles->first()->id == 5 ?
            $this->client = $this->user->client :
            $this->response->logout(JWTAuth::getToken());
    }
    // Funcion para obtener los contactos de un usuario
    public function getListContacts()
    {
        if (($contacts = $this->client->contacts()->with('receiver.user')->get())->count() > 0) {
            $listContacts = [];
            foreach ($contacts as $contact) {
                $data['id'] = $contact->receiver->id;
                $data['receiver']['membership'] = $contact->receiver->user->username;
                $data['receiver']['user']['name'] = $contact->receiver->user->name;
                $data['receiver']['user']['first_surname'] = $contact->receiver->user->first_surname;
                $data['receiver']['user']['second_surname'] = $contact->receiver->user->second_surname;
                array_push($listContacts, $data);
            }
            return $this->response->successResponse('contacts', $listContacts);
        }
        return $this->response->errorResponse('No tienes contactos agregados');
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        $contact = User::where([['username', $request->membership], ['username', '!=', $this->user->username]])->first();
        if ($contact and $contact->roles->first()->id == 5) {
            $data['id'] = $contact->client->id;
            $data['membership'] = $contact->username;
            $data['user']['name'] = $contact->name;
            $data['user']['first_surname'] = $contact->first_surname;
            $data['user']['second_surname'] = $contact->second_surname;
            return $this->response->successResponse('contact', $data);
        }
        return $this->response->errorResponse('Membresía de usuario no disponible');
    }
    // Funcion para agregar un contacto a un contacto
    public function addContact(Request $request)
    {
        if (!($this->client->contacts()->where('receiver_id', $request->id_contact)->exists())) {
            Contact::create($request->merge(['transmitter_id' => $this->client->id, 'receiver_id' => $request->id_contact])->all());
            return $this->response->successResponse('message', 'Contacto agregado correctamente');
        }
        return $this->response->errorResponse('El usuario ya ha sido agregado anteriormente');
    }
    // Funcion para eliminar a un contacto
    public function deleteContact(Request $request)
    {
        if ($contact = $this->client->contacts->where('receiver_id', $request->id_contact)->first()) {
            $contact->delete();
            return $this->response->successResponse('message', 'Contacto eliminado correctamente');
        }
        return $this->response->errorResponse('El contacto no existe');
    }
}
