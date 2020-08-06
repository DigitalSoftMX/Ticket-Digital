<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
use App\DispatcherHistoryPayment;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;
use App\Eucomb\User as EucombUser;
use Exception;

class MainController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function main()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (($car = $user->client->car) != "") {
                $dataCar = array('number_plate' => $car->number_plate, 'type_car' => $car->type_car);
            } else {
                $dataCar = array('number_plate' => '', 'type_car' => '');
            }
            $data['id'] = $user->id;
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['email'] = $user->email;
            $data['client']['membership'] = $user->client->membership;
            $data['client']['current_balance'] = $user->client->current_balance;
            $data['client']['shared_balance'] = $user->client->shared_balance;
            $data['client']['total_shared_balance'] = count(SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0]])->get());
            $data['client']['points'] = $user->client->points;
            $data['client']['image_qr'] = $user->client->image_qr;
            $data['data_car'] = $dataCar;
            return $this->successMessage('user', $data);
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion principal para la ventana de abonos
    public function getListStations()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $data = array();
            foreach (Station::all() as $station) {
                array_push($data, array('id' => $station->id, 'name' => $station->name));
            }
            return $this->successMessage('stations', $data);
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            // Comprobar que el saldo sea un multiplo de 100 y mayor a cero
            if ($request->deposit % 100 == 0 && $request->deposit > 0) {
                // Obteniendo el archivo de imagen de pago
                if (($file = $request->file('image')) != NULL) {
                    if ((strpos($file->getClientMimeType(), 'image')) === false) {
                        return $this->errorMessage('El archivo no es una imagen');
                    }
                    //obtenemos el nombre del archivo
                    // $nombre = $file->getClientOriginalName();      
                    /* Falta guardar la imagen de pago
                    El nombre de la imagen prodria ser el la membresia del cliente y la estacion   */
                    if (($balance = UserHistoryDeposit::where([['client_id', $user->client->id], ['station_id', $request->id_station]])->first()) != null) {
                        $balance->balance += $request->deposit;
                        $balance->status = 1;
                        $balance->save();
                        $this->saveHistoryBalance($balance, 'balance', $request->deposit);
                    } else {
                        $history = new UserHistoryDeposit();
                        $history->client_id = $user->client->id;
                        $history->balance = $request->deposit;
                        $history->station_id = $request->id_station;
                        $history->status = 1;
                        $history->save();
                        $this->saveHistoryBalance($history, 'balance', null);
                    }
                    $user->client->current_balance += $request->deposit;
                    $user->client->update();
                    return $this->successMessage('message', 'Abono realizado correctamente');
                }
                return $this->errorMessage('Debe subir su comprobante');
            }
            return $this->errorMessage('La cantidad debe ser multiplo de $100');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($payments = UserHistoryDeposit::where([['client_id', $user->client->id], ['balance', '>', 0]])->get()) > 0) {
                $deposits = array();
                foreach ($payments as $payment) {
                    array_push($deposits, array(
                        'id' => $payment->id,
                        'balance' => $payment->balance,
                        'status' => $payment->status,
                        'station' => array(
                            'name' => $payment->station->name,
                            'number_station' => $payment->station->number_station
                        )
                    ));
                }
                return $this->successMessage('payments', $deposits);
            }
            return $this->errorMessage('No hay abonos realizados');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (($contact = Client::where([['membership', $request->membership], ['membership', '!=', $user->client->membership]])->first()) != null) {
                $userTicket['id'] = $contact->id;
                $userTicket['membership'] = $contact->membership;
                $userTicket['user']['name'] = $contact->user->name;
                $userTicket['user']['first_surname'] = $contact->user->first_surname;
                $userTicket['user']['second_surname'] = $contact->user->second_surname;
                return $this->successMessage('contact', $userTicket);
            } else {
                $userEucomb = EucombUser::where([['username', $request->membership], ['username', '!=', $user->client->membership]])->first();
                if ($userEucomb != null && $userEucomb->roles[0]->name == 'usuario') {
                    $data = array(
                        'name' => $userEucomb->name . " " . $userEucomb->last_name,
                        'membership' => $userEucomb->username,
                        'message' => 'Descarga la aplicación'
                    );
                    return $this->successMessage('contact', $data);
                }
            }
            return $this->errorMessage('Membresía de usuario no disponible');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para obtener los contactos de un usuario
    public function getListContacts()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($contacts = Contact::where('transmitter_id', $user->client->id)->get()) > 0) {
                $listContacts = array();
                foreach ($contacts as $contact) {
                    $data['id'] = $contact->receiver->id;
                    $data['receiver']['membership'] = $contact->receiver->membership;
                    $data['receiver']['user']['name'] = $contact->receiver->user->name;
                    $data['receiver']['user']['first_surname'] = $contact->receiver->user->first_surname;
                    $data['receiver']['user']['second_surname'] = $contact->receiver->user->second_surname;
                    array_push($listContacts, $data);
                }
                return $this->successMessage('contacts', $listContacts);
            }
            return $this->errorMessage('No tienes contactos agregados');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para agregar un contacto a un contacto
    public function addContact(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (!(Contact::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact]])->exists())) {
                $contact = new Contact;
                $contact->transmitter_id = $user->client->id;
                $contact->receiver_id = $request->id_contact;
                $contact->save();
                return $this->successMessage('message', 'Contacto agregado correctamente');
            }
            return $this->errorMessage('El usuario ya ha sido agregado anteriormente');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para eliminar a un contacto
    public function deleteContact(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($contact = Contact::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact]])->exists()) {
                $contact->delete();
                return $this->successMessage('message', 'Contacto eliminado correctamente');
            }
            return $this->errorMessage('El contacto no existe');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if ($request->balance % 100 == 0 && $request->balance > 0) {
                // Obteniendo el saldo disponible en la estacion correspondiente
                $payment = UserHistoryDeposit::find($request->id_payment);
                if ($payment != null && $payment->client_id == $user->client->id) {
                    if (!($request->balance > $payment->balance)) {
                        if (($receivedBalance = SharedBalance::where([['transmitter_id', $user->client->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id]])->first()) != null) {
                            $receivedBalance->balance += $request->balance;
                            $receivedBalance->status = 1;
                            $receivedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($receivedBalance, 'share', $request->balance);
                            $this->saveHistoryBalance($receivedBalance, 'received', $request->balance);
                        } else {
                            $sharedBalance = new SharedBalance();
                            $sharedBalance->transmitter_id = $user->client->id;
                            $sharedBalance->receiver_id = $request->id_contact;
                            $sharedBalance->balance = $request->balance;
                            $sharedBalance->station_id = $payment->station_id;
                            $sharedBalance->status = 1;
                            $sharedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($sharedBalance, 'share', null);
                            $this->saveHistoryBalance($sharedBalance, 'received', null);
                        }
                        $payment->balance -= $request->balance;
                        $payment->save();
                        // Actualizando el abono total del cliente emisor
                        $user->client->current_balance -= $request->balance;
                        $user->client->save();
                        // Acutalizando el abono compartido del cliente receptor
                        $receiverUser = Client::find($request->id_contact);
                        $receiverUser->shared_balance += $request->balance;
                        $receiverUser->save();
                        return $this->successMessage('message', 'Abono realizado correctamente');
                    }
                    return $this->errorMessage('El deposito es mayor al disponible');
                }
                return $this->errorMessage('El deposito no corresponde al usuario');
            }
            return $this->errorMessage('La cantidad debe ser multiplo de $100');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion que busca los abonos recibidos
    public function listReceivedPayments()
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            if (count($balances = SharedBalance::where([['receiver_id', $user->client->id], ['balance', '>', 0]])->get()) > 0) {
                $receivedBalances = array();
                foreach ($balances as $balance) {
                    $data['id'] = $balance->id;
                    $data['balance'] = $balance->balance;
                    $data['station']['name'] = $balance->station->name;
                    $data['station']['number_station'] = $balance->station->number_station;
                    $data['transmitter']['membership'] = $balance->transmitter->membership;
                    $data['transmitter']['user']['name'] = $balance->transmitter->user->name;
                    $data['transmitter']['user']['first_surname'] = $balance->transmitter->user->first_surname;
                    $data['transmitter']['user']['second_surname'] = $balance->transmitter->user->second_surname;
                    array_push($receivedBalances, $data);
                }
                return $this->successMessage('payments', $receivedBalances);
            }
            return $this->errorMessage('No hay abonos realizados');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para devolver la membresía del cliente y la estacion
    public function useBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            $payment = UserHistoryDeposit::find($request->id_payment);
            if ($payment != null && $payment->client_id == $user->client->id) {
                $station['id'] = $payment->station->id;
                $station['name'] = $payment->station->name;
                $station['number_station'] = $payment->station->number_station;
                return response()->json([
                    'ok' => true,
                    'membership' => $user->client->membership,
                    'station' => $station
                ]);
            }
            return $this->errorMessage('No hay abono en la cuenta');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para devolver informacion de un saldo compartido
    public function useSharedBalance(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            $payment = SharedBalance::find($request->id_payment);
            if ($payment != null && $payment->receiver_id == $user->client->id) {
                $station['id'] = $payment->station->id;
                $station['name'] = $payment->station->name;
                $station['number_station'] = $payment->station->number_station;
                return response()->json([
                    'ok' => true,
                    'tr_membership' => $payment->transmitter->membership,
                    'membership' => $payment->receiver->membership,
                    'station' => $station
                ]);
            }
            return $this->errorMessage('No hay abono en la cuenta');
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        if (($user = Auth::user())->roles[0]->name == 'usuario') {
            try {
                if (($type = $request->type) == 'payment') {
                    if (count($balances = $this->getBalances(new DispatcherHistoryPayment(), $request->start, $request->end, $user, null)) > 0) {
                        $payments = array();
                        foreach ($balances as $balance) {
                            $data['balance'] = $balance->payment;
                            $data['station'] = $balance->station->name;
                            $data['liters'] = $balance->liters;
                            $data['date'] = $balance->created_at->format('Y/m/d');
                            $data['hour'] = $balance->created_at->format('H:i:s');
                            $data['gasoline'] = $balance->gasoline->name;
                            array_push($payments, $data);
                        }
                        return $this->successMessage('payments', $payments);
                    }
                } else {
                    if (count($balances = $this->getBalances(new History(), $request->start, $request->end, $user, $type)) > 0) {
                        $payments = array();
                        switch ($type) {
                            case 'balance':
                                foreach ($balances as $balance) {
                                    $payment = json_decode($balance->action);
                                    $station = Station::find($payment->station_id);
                                    $data['balance'] = $payment->balance;
                                    $data['station'] = $station->name;
                                    $data['date'] = $balance->created_at->format('Y/m/d');
                                    $data['hour'] = $balance->created_at->format('H:i:s');
                                    array_push($payments, $data);
                                }
                                break;
                            case 'share':
                                $payments = $this->getSharedBalances($balances, 'receiver_id');
                                break;
                            case 'received':
                                $payments = $this->getSharedBalances($balances, 'transmitter_id');
                                break;
                        }
                        return $this->successMessage('balances', $payments);
                    }
                }
                return $this->errorMessage('Sin movimientos en la cuenta');
            } catch (Exception $e) {
                return $this->errorMessage('Error de consulta por fecha');
            }
        }
        return $this->errorMessage('Usuario no autorizado');
    }
    // Funcion para guardar historial de abonos a la cuenta del cliente
    private function saveHistoryBalance($history, $type, $balance)
    {
        $historyBalance = new History();
        if ($balance != null) {
            $history->balance = $balance;
        }
        switch ($type) {
            case 'balance':
                $historyBalance->client_id = $history->client_id;
                break;
            case 'share':
                $historyBalance->client_id = $history->transmitter_id;
                break;
            case 'received':
                $historyBalance->client_id = $history->receiver_id;
                break;
        }
        $historyBalance->action = $history;
        $historyBalance->type = $type;
        $historyBalance->save();
    }
    // Funcion para devolver el arreglo de historiales
    private function getBalances($model, $start, $end, $user, $type)
    {
        $query = [['client_id', $user->client->id]];
        if ($type != null) {
            $query[1] = ['type', $type];
        }
        if ($start == "" && $end == "") {
            $balances = $model::where($query)->get();
        } elseif ($start == "") {
            $balances = $model::where($query)->whereDate('created_at', '<=', $end)->get();
        } elseif ($end == "") {
            $balances = $model::where($query)->whereDate('created_at', '>=', $start)->get();
        } else {
            if ($start > $end) {
                return null;
            } else {
                $balances = $model::where($query)->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();
            }
        }
        return $balances;
    }
    // Obteniendo el historial enviodo o recibido
    private function getSharedBalances($balances, $person)
    {
        $payments = array();
        foreach ($balances as $balance) {
            $action = json_decode($balance->action);
            $station = Station::find($action->station_id);
            $client = Client::find($action->$person);
            $payment['station'] = $station->name;
            $payment['balance'] = $action->balance;
            $payment['membership'] = $client->membership;
            $payment['name'] = $client->user->name . ' ' . $client->user->first_surname . ' ' . $client->user->second_surname;
            $payment['date'] = $balance->created_at->format('Y/m/d');
            array_push($payments, $payment);
        }
        return $payments;
    }
    // Funcion mensajes de error
    private function errorMessage($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
    // Funcion mensaje correcto
    private function successMessage($name, $data)
    {
        return response()->json([
            'ok' => true,
            $name => $data
        ]);
    }
}
