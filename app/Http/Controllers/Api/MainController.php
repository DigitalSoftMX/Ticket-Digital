<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Contact;
use App\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\Station;
use App\UserHistoryDeposit;
use Illuminate\Support\Facades\Auth;
use App\Eucomb\User as EucombUser;

class MainController extends Controller
{
    // funcion para obtener informacion del usuario hacia la pagina princial
    public function main()
    {
        $user = Auth::user();
        if (Auth::user()->roles[0]->name == 'usuario') {
            $data = array(
                'id' => $user->id,
                'name' => $user->name,
                'first_surname' => $user->first_surname,
                'second_surname' => $user->second_surname,
                'email' => $user->email,
                'sex' => $user->sex,
                'phone' => $user->phone,
                'client' => array(
                    'membership' => $user->client->membership,
                    'current_balance' => $user->client->current_balance,
                    'shared_balance' => $user->client->shared_balance,
                    'total_shared_balance' => count(SharedBalance::where('receiver_id', $user->client->id)->get()),
                    'points' => $user->client->points,
                    'image_qr' => $user->client->image_qr,
                    'birthdate' => $user->client->birthdate
                )
            );
            return $this->successMessage('user', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion principal para la ventana de abonos
    public function getListStations()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $data = array();
            foreach (Station::all() as $station) {
                array_push($data, array(
                    'id' => $station->id,
                    'name' => $station->name,
                ));
            }
            return $this->successMessage('stations', $data);
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $user = Auth::user()->client;
            // Comprobar que el saldo sea un multiplo de 100 y mayor a cero
            if ($request->deposit % 100 == 0 && $request->deposit > 0) {
                // Obteniendo el archivo de imagen de pago
                $file = $request->file('image');
                if ($file != NULL) {
                    if ((strpos($file->getClientMimeType(), 'image')) === false) {
                        return $this->errorMessage('El archivo no es una imagen');
                    } else {
                        //obtenemos el nombre del archivo
                        // $nombre = $file->getClientOriginalName();      
                        /* Falta guardar la imagen de pago
                    El nombre de la imagen prodria ser el la membresia del cliente y la estacion   */
                        $station = UserHistoryDeposit::where([['client_id', $user->id], ['station_id', $request->id_station]])->first();
                        if ($station != null) {
                            $station->balance += $request->deposit;
                            $station->status = 1;
                            $station->save();
                            $this->saveHistoryBalance($station, 'balance', $request->deposit);
                        } else {
                            $history = new UserHistoryDeposit();
                            $history->client_id = $user->id;
                            $history->balance = $request->deposit;
                            $history->station_id = $request->id_station;
                            $history->status = 1;
                            $history->save();
                            $this->saveHistoryBalance($history, 'balance', 0);
                        }
                        $user->current_balance += $request->deposit;
                        $user->update();
                        return $this->successMessage('message', 'Abono realizado correctamente');
                    }
                } else {
                    return $this->errorMessage('Debe subir su comprobante');
                }
            } else {
                return $this->errorMessage('La cantidad debe ser multiplo de $100');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getPersonalPayments()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $payments = UserHistoryDeposit::where('client_id', Auth::user()->client->id)->get();
            if (count($payments) != 0) {
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
            } else {
                return $this->errorMessage('No hay abonos realizados');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para obtener un contacto buscado por un usuario tipo cliente
    public function lookingForContact(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            if (Auth::user()->client->membership != $request->membership) {
                $contact = Client::where('membership', $request->membership)->first();
                if ($contact != null) {
                    $user = array(
                        'id' => $contact->id,
                        'membership' => $contact->membership,
                        'user' => array(
                            'name' => $contact->user->name,
                            'first_surname' => $contact->user->first_surname,
                            'second_surname' => $contact->user->second_surname
                        )
                    );
                    return $this->successMessage('contact', $user);
                } else {
                    $user = EucombUser::where('username', $request->membership)->first();
                    if ($user != null && $user->roles[0]->name == 'usuario') {
                        $data = array(
                            'name' => $user->name . " " . $user->last_name,
                            'membership' => $user->username,
                            'message' => 'Descarga la aplicación'
                        );
                        return $this->successMessage('contact', $data);
                    } else {
                        return $this->errorMessage('Usuario no registrado');
                    }
                }
            } else {
                return $this->errorMessage('Membresia del cliente');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para obtener los contactos de un usuario
    public function getListContacts()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $contacts = Contact::where('transmitter_id', Auth::user()->client->id)->get();
            if (count($contacts) != 0) {
                $listContacts = array();
                foreach ($contacts as $contact) {
                    $data = array(
                        'id' => $contact->receiver->id,
                        'receiver' => array(
                            'membership' => $contact->receiver->membership,
                            'user' => array(
                                'name' => $contact->receiver->user->name,
                                'first_surname' => $contact->receiver->user->first_surname,
                                'second_surname' => $contact->receiver->user->second_surname
                            )
                        )
                    );
                    array_push($listContacts, $data);
                }
                return $this->successMessage('contacts', $listContacts);
            } else {
                return $this->errorMessage('No tienes contactos agregados');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para agregar un contacto a un contacto
    public function addContact(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            if (!(Contact::where([['transmitter_id', Auth::user()->client->id], ['receiver_id', $request->id_contact]])->exists())) {
                $contact = new Contact;
                $contact->transmitter_id = Auth::user()->client->id;
                $contact->receiver_id = $request->id_contact;
                $contact->save();
                return $this->successMessage('message','Contacto agregado correctamente');
            } else {
                return $this->errorMessage('El usuario ya ha sido agregado anteriormente');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para eliminar a un contacto
    public function deleteContact(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $contact = Contact::where([['transmitter_id', Auth::user()->client->id], ['receiver_id', $request->id_contact]])->first();
            $contact->delete();
            return $this->successMessage('message', 'Contacto eliminado correctamente');
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            if ($request->balance % 100 == 0 && $request->balance > 0) {
                // Obteniendo el saldo disponible en la estacion correspondiente
                $user = Auth::user()->client;
                $payment = UserHistoryDeposit::find($request->id_payment);
                if ($payment != null && $payment->client_id == $user->id) {
                    if (!($request->balance > $payment->balance)) {
                        $receivedBalance = SharedBalance::where([['transmitter_id', $user->id], ['receiver_id', $request->id_contact], ['station_id', $payment->station_id]])->first();
                        if ($receivedBalance != null) {
                            $receivedBalance->balance += $request->balance;
                            $receivedBalance->status = 1;
                            $receivedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($receivedBalance, 'share', $request->balance);
                            $this->saveHistoryBalance($receivedBalance, 'received', $request->balance);
                        } else {
                            $sharedBalance = new SharedBalance();
                            $sharedBalance->transmitter_id = Auth::user()->client->id;
                            $sharedBalance->receiver_id = $request->id_contact;
                            $sharedBalance->balance = $request->balance;
                            $sharedBalance->station_id = $payment->station_id;
                            $sharedBalance->status = 1;
                            $sharedBalance->save();
                            // Guardando historial de transaccion
                            $this->saveHistoryBalance($sharedBalance, 'share', 0);
                            $this->saveHistoryBalance($sharedBalance, 'received', 0);
                        }
                        $payment->balance -= $request->balance;
                        $payment->save();
                        // Actualizando el abono total del cliente emisor
                        $user->current_balance -= $request->balance;
                        $user->save();
                        // Acutalizando el abono compartido del cliente receptor
                        $receiverUser = Client::find($request->id_contact);
                        $receiverUser->shared_balance += $request->balance;
                        $receiverUser->save();
                        return $this->successMessage('message', 'Abono realizado correctamente');
                    } else {
                        return $this->errorMessage('El deposito es mayor al disponible');
                    }
                } else {
                    return $this->errorMessage('El deposito no corresponde al usuario');
                }
            } else {
                return $this->errorMessage('La cantidad debe ser multiplo de $100');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion que busca los abonos recibidos hacia el usuario por parte de otros clientes
    public function listReceivedPayments()
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $balances = SharedBalance::where('receiver_id', Auth::user()->client->id)->get();
            if (count($balances) != 0) {
                $receivedBalances = array();
                foreach ($balances as $balance) {
                    $data = array(
                        'id' => $balance->id,
                        'balance' => $balance->balance,
                        'station' => array(
                            'name' => $balance->station->name,
                            'number_station' => $balance->station->number_station,
                        ),
                        'transmitter' => array(
                            'membership' => $balance->transmitter->membership,
                            'user' => array(
                                'name' => $balance->transmitter->user->name,
                                'first_surname' => $balance->transmitter->user->first_surname,
                                'second_surname' => $balance->transmitter->user->second_surname,
                            )
                        )
                    );
                    array_push($receivedBalances, $data);
                }
                return $this->successMessage('payments', $receivedBalances);
            } else {
                return $this->errorMessage('No hay abonos realizados');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para devolver la membresía del cliente y la estacion
    public function useBalance(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $payment = UserHistoryDeposit::find($request->id_payment);
            if ($payment != null && $payment->client_id == Auth::user()->client->id) {
                $station = array(
                    'id' => $payment->station->id,
                    'name' => $payment->station->name,
                    'number_station' => $payment->station->number_station
                );
                return response()->json([
                    'ok' => true,
                    'membership' => Auth::user()->client->membership,
                    'station' => $station
                ]);
            } else {
                return $this->errorMessage('No hay abono en la cuenta');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para devolver informacion de un saldo compartido
    public function useSharedBalance(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            $payment = SharedBalance::find($request->id_payment);
            if ($payment != null && $payment->receiver_id == Auth::user()->client->id) {
                $station = array(
                    'id' => $payment->station->id,
                    'name' => $payment->station->name,
                    'number_station' => $payment->station->number_station
                );
                return response()->json([
                    'ok' => true,
                    'tr_membership' => $payment->transmitter->membership,
                    'membership' => $payment->receiver->membership,
                    'station' => $station
                ]);
            } else {
                return $this->errorMessage('No hay abono en la cuenta');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para devolver el historial de abonos a la cuenta del usuario
    public function history(Request $request)
    {
        if (Auth::user()->roles[0]->name == 'usuario') {
            if ($request->start == "" && $request->end == "") {
                $historyBalances = History::where([['client_id', Auth::user()->client->id], ['type', $request->type]])->get();
            } elseif ($request->start == "") {
                $historyBalances = History::where([['client_id', Auth::user()->client->id], ['type', $request->type]])->whereDate('created_at', '<=', $request->end)->get();
            } elseif ($request->end == "") {
                $historyBalances = History::where([['client_id', Auth::user()->client->id], ['type', $request->type]])->whereDate('created_at', '>=', $request->start)->get();
            } else {
                if ($request->start > $request->end) {
                    return $this->errorMessage('Error de consulta por fecha');
                } else {
                    $historyBalances = History::where([['client_id', Auth::user()->client->id], ['type', $request->type]])->whereDate('created_at', '>=', $request->start)->whereDate('created_at', '<=', $request->end)->get();
                }
            }
            if (count($historyBalances) > 0) {
                $balances = array();
                switch ($request->type) {
                    case 'balance':
                        foreach ($historyBalances as $historyBalance) {
                            $balance = json_decode($historyBalance->action);
                            $station = Station::find($balance->station_id);
                            $action = array(
                                'balance' => $balance->balance,
                                'station' => $station->name,
                                'date' => $historyBalance->created_at->format('Y/m/d')
                            );
                            array_push($balances, $action);
                        }
                        break;
                    case 'share':
                        $balances = $this->getSharedBalanceHistory($historyBalances, 'receiver_id');
                        break;
                    case 'received':
                        $balances = $this->getSharedBalanceHistory($historyBalances, 'transmitter_id');
                        break;
                }
                return $this->successMessage('balances', $balances);
            } else {
                return $this->errorMessage('No se ha realizado abonos a su cuenta');
            }
        } else {
            return $this->errorMessage('Usuario no autorizado');
        }
    }
    // Funcion para guardar historial de abonos a la cuenta del cliente
    private function saveHistoryBalance($history, $type, $balance)
    {
        $historyBalance = new History();
        if ($balance != 0) {
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
    // Obteniendo el historial enviodo o recibido
    private function getSharedBalanceHistory($historyBalances, $person)
    {
        $balances = array();
        foreach ($historyBalances as $historyBalance) {
            $balance = json_decode($historyBalance->action);
            $station = Station::find($balance->station_id);
            $data = Client::find($balance->$person);
            $action = array(
                'station' => $station->name,
                'balance' => $balance->balance,
                'membership' => $data->membership,
                'name' => $data->user->name . ' ' . $data->user->first_surname . ' ' . $data->user->second_surname,
                'date' => $historyBalance->created_at->format('Y/m/d')
            );
            array_push($balances, $action);
        }
        return $balances;
    }
}
