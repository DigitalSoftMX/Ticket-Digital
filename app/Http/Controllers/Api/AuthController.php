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
use App\Repositories\ResponsesAndLogout;
use App\SalesQr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Station;
use App\User;
use Carbon\Carbon;
use Exception;
use Google_Client;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\DispatcherReference;
use App\ReferredDispatcherClient;
use App\Repositories\Actions;

class AuthController extends Controller
{
    private $response, $clientGoogle;

    public function __construct(ResponsesAndLogout $response)
    {
        $this->response = $response;
        $this->clientGoogle = new Google_Client(['client_id' => '358591636304-5sehkr6cb2t13lutk9rb76vjocv9rj0v.apps.googleusercontent.com']);
    }
    // Metodo para inicar sesion
    public function login(Request $request)
    {
        if ($u = User::where('username', $request->email)->first()) {
            if ($u->email) {
                $user = User::where('email', $u->email)->get();
                $request->merge(['email' => $u->email]);
            } else {
                return $this->response
                    ->errorResponse(
                        'No existe un correo electrónico registrado. Ingrese un correo electrónico.',
                        $u->id
                    );
            }
        } else {
            $user = User::where('email', $request->email)->get();

            // Validar si el rol usuario esta activo
            if(count($user)>0 && $user!=NULL && $user[0]->roles[0]->name == 'usuario' && $user[0]->active == 0){
                return $this->response->errorResponse('Lo sentimos, la cuenta no esta activa.', null);
            }
        }
        switch ($user->count()) {
            case 0:
                return $this->response->errorResponse('Lo sentimos, la cuenta no esta registrada.', null);
            case 1:

                if ($user[0]->external_id)
                    return $this->response->errorResponse('Intente iniciar sesión con su cuenta de google');

                foreach ($user->first()->roles as $rol) {
                    if ($rol->id == 4 || $rol->id == 5) {
                        $validator = Validator::make($request->only('email'), ['email' => 'email']);
                        return ($validator->fails()) ?
                            $this->response->errorResponse(
                                'Por favor, ingrese un nuevo correo electrónico.',
                                $user[0]->id
                            ) : $this->getToken($request, $user[0], $rol->id);
                    }
                }
                return $this->response->errorResponse('Usuario no autorizado', null);
            default:
                return $u ?
                    $this->response->errorResponse('Por favor, ingrese un nuevo correo electrónico.', $u->id) :
                    $this->response->errorResponse('Intente ingresar con su membresía.', null);
        }
    }
    public function loginGoogle(Request $request)
    {
        $userGoogle = $this->clientGoogle->verifyIdToken($request->idToken);

        if ($userGoogle) {
            $user = User::where('external_id', $userGoogle['sub'])->first();

            if ($user) {
                $request->merge(['email' => $user->email, 'password' => $user->username]);
                return $this->getToken($request, $user, 5);
            }

            return $this->response->errorResponse('El usuario no ha sido registrado anteriormente');
        }
        return $this->response->errorResponse('Intente más tarde');
    }
    // Registro con Google
    public function registerGoogle(Request $request)
    {
        // Verificacion del usuario de google
        $userGoogle = $this->clientGoogle->verifyIdToken($request->idToken);

        if ($userGoogle) {

            $userExists = User::where('email', $userGoogle['email'])->first();
            if (!$userExists) {
                // Membresia aleatoria no repetible
                while (true) {
                    $membership = 'E' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
                    if (!(User::where('username', $membership)->exists()))
                        break;
                }

                $request->merge([
                    'username' => $membership, 'external_id' => $userGoogle['sub'],
                    'email' => $userGoogle['email'], 'name' => $userGoogle['given_name'],
                    'first_surname' => $userGoogle['family_name'], 'password' => bcrypt($membership)
                ]);

                $user = User::create($request->all());
                $request->merge(['user_id' => $user->id, 'points' => Empresa::find(1)->points, 'image' => $membership]);
                Client::create($request->all());
                $user->roles()->attach('5');
                Storage::disk('public')->deleteDirectory($user->username);
                $request->merge(['password' => $membership]);
                return $this->getToken($request, $user, 5);
            }

            return $this->response->errorResponse('Ya existe un usuario con el correo electrónico');
        }

        return $this->response->errorResponse('Intente más tarde');
    }

    // Metodo para registrar a un usuario nuevo
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'email' => ['required', 'email', Rule::unique((new User)->getTable())],
            'password' => 'required|string|min:6',
            'number_plate' => $request->number_plate ? [Rule::unique((new DataCar())->getTable())] : '',
        ]);
        if ($validator->fails())
            return $this->response->errorResponse($validator->errors(), null);
        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'E' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
            if (!(User::where('username', $membership)->exists()))
                break;
        }
        $password = $request->password;
        $request->merge(['username' => $membership, 'password' => bcrypt($request->password)]);
        $user = User::create($request->all());
        $request->merge(['user_id' => $user->id, 'points' => Empresa::find(1)->points, 'image' => $membership]);
        Client::create($request->all());
        $user->roles()->attach('5');
        if ($request->number_plate != "" || $request->type_car != "") {
            $request->merge(['client_id' => $user->client->id]);
            DataCar::create($request->only(['client_id', 'number_plate', 'type_car']));
        }
        Storage::disk('public')->deleteDirectory($user->username);
        $request->merge(['password' => $password]);
        return $this->getToken($request, $user, 5);
    }

    // Metodo para enviar notificacion por whatsApp
    private function sendNotificationByWhatsap($user, $body)
    {
        $action = new Actions();
        $action->notificationByWhatsapp($user->phone, $body);
    }

    // Metodo para registrar a un usuario nuevo con whatsapp
    public function registerW(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|min:3',
            'first_surname' => 'required|string|min:3',
            'email'         => 'required|email|unique:users',
            'phone'         => 'required|unique:users|min:10|regex:/^[0-9\+]{1,}[0-9\-]{3,15}$/',
            'password'      => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'referrer_code' => 'nullable|string|exists:dispatcher_references,referrer_code',  // Codigo de referencia que fu compartido por el despachador
            'ids'           => 'nullable|string',
        ],
        [
            'referrer_code.exists' => 'El código de referencia no existe en el sistema',
        ]);
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        // Membresia aleatoria no repetible
        while (true) {
            $membership = 'E' . substr(Carbon::now()->format('Y'), 2) . rand(100000, 999999);
            if (!(User::where('username', $membership)->exists()))
                break;
        }

        $dReference = DispatcherReference::where('referrer_code', $request->referrer_code)->first(); // Obtener informacion del codigo de referencia

        while (true) { $code = rand(100000,999999); if (!(User::where('verify_code', $code)->exists())) break; } // Obtener codigo no repetible

        $password = $request->password;
        $request->merge(['username'=>$membership, 'password'=>bcrypt($request->password), 'active'=>0, 'verify_code'=>$code]);
        // $user = User::create($request->all()); //Crea usuario
        $user = User::create($request->only('name', 'first_surname', 'email', 'phone', 'password', 'referrer_code', 'username', 'active', 'verify_code')); //Crea usuario

        $request->merge(['user_id'=>$user->id, 'points'=>Empresa::find(1)->points, 'image'=>$membership]);
        Client::create($request->all()); // Crear cliente
        $user->roles()->attach(5);

        // Crear registro en referred dispatcher clients
        if(!empty($request->referrer_code)) {
            $request->merge(['client_id'=>$user->client->id, 'user_id'=>$dReference->user_id]);
            ReferredDispatcherClient::create($request->only(['user_id', 'client_id']));
        }

        $data['id'] = $user->id;
        $data['name'] = $user->name;
        $data['first_surname'] = $user->first_surname;
        $data['email'] = $user->email;
        $data['phone'] = $user->phone;
        $data['username'] = $user->username;
        $data['active'] = $user->active;
        $data['verify_code']= $user->verify_code;

        $body = 'Hola, tu código para activar tu cuenta es: '.$code;
        $this->sendNotificationByWhatsap($user,$body);
        $data['message'] = "Por favor revise su WhatsApp, acaba de recibir un mensaje para activar su cuenta";

        return $this->successReponse('data', $data);
    }

    // Validar cuenta para clientes de la app
    public function validateAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'  => 'required|email|exists:users,email',
            'code'   => 'required'
        ]);
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        try {
            if (!$user = User::where('email', $request->email)->where('verify_code', $request->code)->first())
                return $this->response->errorResponse('El código no es correcto. Por favor, verifícalo', 404);

            $token = JWTAuth::fromUser($user); //Genera token
            $user->update(['verify_code'=>null, 'active'=>1, 'remember_token'=>$token]);

            $data['token'] = $token;
            $data['message'] = 'La cuenta se ha verificado correctamente';
            return $this->successReponse('data',$data);
        } catch (\Exception $e) {
            error_log('Ocurrió un error interno al validar la cuenta: '.$e->getMessage());
            return $this->response->errorResponse('Ocurrió un error interno al validar la cuenta');
        }
    }

    // Metodo para reenviar codigo para validar cuenta
    public function resendCodeValidateAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|exists:users,email',
            'phone'         => 'required|min:10|regex:/^[0-9\+]{1,}[0-9\-]{3,15}$/',
        ]);
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        try {
            $user = User::where('email', $request->email)->first();
            $role = $user->roles->first()->name;

            if ($role != 'usuario')
                return $this->response->errorResponse('Usuario no autorizado');

            // Reenviar codigo
            if($user->verify_code){
                $code = $user->verify_code;
            }else{
                while (true) { $code = rand(100000,999999); if (!(User::where('verify_code', $code)->exists())) break; } // Obtener codigo no repetible
            }
            $user->update(['phone'=>$request->phone, 'active'=>0, 'verify_code'=>$code, 'remember_token'=>NULL]); // Actualiza datos

            $body = 'Hola, tu código para activar tu cuenta es: '.$code;
            $this->sendNotificationByWhatsap($user,$body);
            $data['message'] = "Por favor revise su WhatsApp, acaba de recibir un mensaje para activar su cuenta";

            return $this->successReponse('data', $data);
        } catch (\Exception $e) {
            error_log('Ocurrió un error interno al reenviar código '.$e->getMessage());
            return $this->response->errorResponse('Ocurrió un error interno al reenviar código');
        }
    }

    // Metodo para generar contraseñas alfanumericas
    private function generatePasswords($long=6)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $long; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $password .= $characters[$index];
        }
        return $password;
    }

    // Enviar datos de acceso para el app
    public function recoverAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|exists:users,email',
            ],
            [
                'required'      => 'El :attribute es requerido',
                'email'         => 'La dirección de correo electrónico no es válida',
                'exists'        => 'El dato ingresado no existe en el sistema',
            ]
        );
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        try {
            $user = User::where('email', $request->email)->first();
            $role = $user->roles->first()->name;

            if ($role != 'usuario')
                return $this->response->errorResponse('Usuario no autorizado');

            $password = $this->generatePasswords(8);
            $user->password = bcrypt($password);
            $user->save();

            // Enviar email
            $action = new Actions();
            $dataTmp['subject'] = "Recuperar cuenta";
            $dataTmp['email'] = $request->email;
            $dataTmp['password'] = $password;
            $dataTmp['view'] = 'emails.recover-account';
            $action->notificationByEmail($dataTmp);
            $data['message'] = 'La cuenta se ha recuperado de manera correcta, por favor cambie la contraseña en la sección de perfil';

            return $this->successReponse('data', $data);
        } catch (\Exception $e) {
            error_log('Ocurrió un error interno al recuperar cuenta '.$e->getMessage());
            return $this->response->errorResponse('Ocurrió un error interno al recuperar cuenta');
        }
    }

    // Metodo para actualizar teléfono y enviar código para validar cuenta
    public function updatePhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email|exists:users,email',
            'phone'         => 'required|min:10|regex:/^[0-9\+]{1,}[0-9\-]{3,15}$/',
        ]);
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        try {
            $user = User::where('email', $request->email)->first();
            $role = $user->roles->first()->name;

            if ($role != 'usuario')
                return $this->response->errorResponse('Usuario no autorizado');

            while (true) { $code = rand(100000,999999); if (!(User::where('verify_code', $code)->exists())) break; } // Obtener codigo no repetible

            $user->update(['phone'=>$request->phone, 'active'=>0, 'verify_code'=>$code, 'remember_token'=>NULL]); // Actualiza datos

            $body = 'Hola, tu código para activar tu cuenta es: '.$code;
            $this->sendNotificationByWhatsap($user,$body);
            $data['message'] = "El número de teléfono se ha actualizado correctamente, por favor revise su WhatsApp, acaba de recibir un mensaje para activar su cuenta";

            return $this->successReponse('data', $data);
        } catch (\Exception $e) {
            error_log('Ocurrió un error interno al actualizar teléfono '.$e->getMessage());
            return $this->response->errorResponse('Ocurrió un error interno al actualizar teléfono');
        }
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
            return $this->response->errorResponse($validator->errors(), $request->id);
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
            return $this->response->errorResponse('Token inválido', null);
        }
    }
    // Metodo para iniciar sesion, delvuelve el token
    private function getToken($request, $user, $rol)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password')))
            return $this->response->errorResponse('Datos incorrectos', null);
        $user->update(['remember_token' => $token]);
        if ($rol == 5) {
            if ($user->client == null) {
                $request->merge(['user_id' => $user->id, 'current_balance' => 0, 'shared_balance' => 0, 'points' => 0, 'image' => $user->username, 'visits' => 0, 'acive' => 0]);
                $user->client = Client::create($request->except('ids'));
                $user->client->save();
            }
            if ($user->client->ids == null) {
                if (count($dataPoints = Tarjeta::where('number_usuario', $user->username)->get()) > 0) {
                    $user->client->points += $dataPoints->sum('totals');
                    $user->client->visits += $dataPoints->sum('visits');
                    // $user->client->update(['points' => $dataPoints->sum('totals'), 'visits' => $dataPoints->sum('visits')]);
                    $user->client->save();
                    foreach ($dataPoints as $dataPoint) {
                        $dataPoint->delete();
                    }
                }
                $this->ticketsToSalesQRs(Ticket::where([['number_usuario', $user->username], ['descrip', 'LIKE', '%puntos sumados%']])->get(), 1, $user->client->id);
                $this->ticketsToSalesQRs(Ticket::where([['number_usuario', $user->username], ['descrip', 'LIKE', '%Puntos Dobles Sumados%']])->get(), 2, $user->client->id);
                $this->ticketsToSalesQRs(Ticket::where([['number_usuario', $user->username], ['descrip', 'LIKE', '%Información errónea%']])->get(), 3, $user->client->id);
                $this->ticketsToSalesQRs(Ticket::where([['number_usuario', $user->username], ['descrip', 'LIKE', '%pendiente%']])->get(), 4, $user->client->id);
                $this->ticketsToSalesQRs(Ticket::where('number_usuario', $user->username)->get(), 5, null);
                foreach (History::where('number_usuario', $user->username)->get() as $history) {
                    try {
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
                    } catch (Exception $e) {
                    }
                    $history->delete();
                }
                foreach (Canje::where('number_usuario', $user->username)->get() as $canje) {
                    try {
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
                    } catch (Exception $e) {
                    }
                    $canje->delete();
                }
            }
            $user->client->update($request->only('ids'));
        }
        return $this->successReponse('token', $token);
    }
    // Metodo para copiar el historial de Tickets a SalesQR
    private function ticketsToSalesQRs($tickets, $status, $id)
    {
        foreach ($tickets as $ticket) {
            if ($status != 5) {
                try {
                    $dataSaleQr = new SalesQr();
                    $dataSaleQr->sale = $ticket->number_ticket;
                    $dataSaleQr->station_id = $ticket->id_gas;
                    $dataSaleQr->client_id = $id;
                    $dataSaleQr->created_at = $ticket->created_at;
                    $dataSaleQr->updated_at = $ticket->updated_at;
                    if ($status == 1 || $status == 2) {
                        $dataSaleQr->gasoline_id = Gasoline::where('name', 'LIKE', '%' . $ticket->producto . '%')->first()->id;
                        $dataSaleQr->liters = $ticket->litro;
                        $dataSaleQr->points = $ticket->punto;
                        $dataSaleQr->payment = $ticket->costo;
                    }
                    if ($status == 3 || $status == 4) {
                        $dataSaleQr->gasoline_id = null;
                        $dataSaleQr->liters = 0;
                        $dataSaleQr->points = 0;
                        $dataSaleQr->payment = 0;
                    }
                    $dataSaleQr->save();
                } catch (Exception $e) {
                }
            }
            $ticket->delete();
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

    // >>>>>DESPACHADOR REFERIDO
    // Metodo para crear codigo de referido
    public function createReferralCode($prefix_membership="")
    {
        $year = date('y');
        $random = str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
        return $prefix_membership.$year.'-'. $random;
    }

    // Metodo para registrar a un usuario despachador referido
    public function registerReferredDispatcher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|min:3',
            'first_surname' => 'required|string|min:3',
            'email'         => 'required|email|unique:users',
            // 'phone'         => 'required|unique:users|min:10|regex:/^[0-9\+]{1,}[0-9\-]{3,15}$/',
            'phone'         => 'required|min:10|regex:/^[0-9\+]{1,}[0-9\-]{3,15}$/',
            'password'      => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'station_id'    => 'required|integer|exists:station,id',  //Id de estacion
            'adm_email'     => 'required|email|exists:users,email',  // Email del administrador
        ],
        [
            'adm_email.exists' => 'El :attribute :input no existe en el sistema',
        ]);
        if ($validator->fails()){ return $this->response->errorResponse($validator->errors()); }

        $user = User::where('email', $request->adm_email)->first();
        $role = $user->roles->first()->name;

        if ($role != 'admin_master' && $role != 'admin_eucomb')
            return $this->response->errorResponse('Usuario no autorizado');

        try {
            $station = Station::where('id', $request->station_id)->first();
            $prefix = $station->abrev ?? '';
            while (true) { $referrerCode = $this->createReferralCode($prefix); if (!(DispatcherReference::where('referrer_code', $referrerCode)->exists())) break; } // Obtener codigo de referencia no repetible para el nuevo usuario

            // Obtener username no repetible
            while (true) { $username = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10); if (!(User::where('username', $username)->exists())) break; }

            // Crea usuario
            $password = $request->password;
            $request->merge(['password'=>bcrypt($request->password), 'username'=>$username]);
            $user = User::create($request->all());
            $user->roles()->attach(8);

            // Salvar codigo de referencia
            $request->merge(['user_id'=>$user->id, 'referrer_code'=>$referrerCode]);
            $dReference = DispatcherReference::create($request->all());

            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['email'] = $user->email;
            $data['phone'] = $user->phone;
            $data['referrer_code'] = $dReference->referrer_code;
            $data['dispatcher_id'] = $user->username;
            $data['station']['id'] = $dReference->station->id;
            $data['station']['name'] = $dReference->station->name;
            $data['station']['number_station'] = $dReference->station->number_station_alvic ?? $dReference->station->number_station ?? '';

            return $this->response->successResponse('data', $data);
        } catch (\Exception $e) {
            error_log('Ocurrió un error interno al crear registro '.$e->getMessage());
            return $this->response->errorResponse('Ocurrió un error interno al crear registro');
        }
    }

}
