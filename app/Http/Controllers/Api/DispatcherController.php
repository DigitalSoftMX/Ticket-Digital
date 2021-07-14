<?php

namespace App\Http\Controllers\Api;

use App\Sale;
use App\Gasoline;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RegisterTime;
use App\Repositories\ResponsesAndLogout;
use App\Schedule;
use App\User;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class DispatcherController extends Controller
{
    private $user, $dispatcher, $station, $time, $schedule, $todo;
    public function __construct(ResponsesAndLogout $todo)
    {
        $this->todo = $todo;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->roles->first()->id != 4) {
            $this->todo->logout(JWTAuth::getToken());
        } else {
            $this->dispatcher = $this->user->dispatcher;
            $this->station = $this->user->dispatcher->station;
            $this->time = $this->user->dispatcher->times->last();
            $this->schedule = Schedule::where('station_id', $this->station->id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
        }
    }
    // Funcion principal del despachador
    public function index()
    {
        $payments = $this->time != null ? Sale::whereDate('created_at', now()->format('Y-m-d'))->where([['dispatcher_id', $this->dispatcher->id], ['station_id', $this->station->id], ['time_id', $this->time->id]])->get() : [];
        $totalPayment = $this->time != null ? $payments->sum('payment') : 0;
        $data['id'] = $this->user->id;
        $data['name'] = $this->user->name;
        $data['first_surname'] = $this->user->first_surname;
        $data['second_surname'] = $this->user->second_surname;
        $data['dispatcher_id'] = $this->user->username;
        $data['station']['id'] = $this->station->id;
        $data['station']['name'] = $this->station->name;
        $data['station']['number_station'] = $this->station->number_station;
        $data['schedule']['id'] = $this->schedule->id;
        $data['schedule']['name'] = $this->schedule->name;
        $data['number_payments'] = count($payments);
        $data['total_payments'] = $totalPayment;
        return $this->todo->successResponse('user', $data);
    }
    // Registro de inicio de turno y termino de turno
    public function startEndTime(Request $request)
    {
        switch ($request->time) {
            case 'true':
                if ($this->time != null) {
                    if ($this->time->status == 6) {
                        return $this->todo->errorResponse('Finalice el turno actual para iniciar otro');
                    }
                }
                RegisterTime::create([
                    'dispatcher_id' => $this->dispatcher->id, 'station_id' => $this->station->id, 'schedule_id' => $this->schedule->id, 'status' => 6
                ]);
                return $this->todo->successResponse('message', 'Inicio de turno registrado');
            case 'false':
                if ($this->time != null) {
                    $this->time->update(['status' => 8]);
                    return $this->todo->successResponse('message', 'Fin de turno registrado');
                }
                return $this->todo->errorResponse('Turno no registrado');
        }
        return $this->todo->errorResponse('Registro no valido');
    }
    // Metodo para obtner la lista de gasolina
    public function gasolineList()
    {
        $gasolines = array();
        $islands = array();
        foreach (Gasoline::all() as $gasoline) {
            array_push($gasolines, array('id' => $gasoline->id, 'name' => $gasoline->name));
        }
        foreach ($this->station->islands as $island) {
            array_push($islands, array('island' => $island->island, 'bomb' => $island->bomb));
        }
        $data['url'] = 'http://' . $this->station->dns . '/sales/public/record.php';
        $data['islands'] = $islands;
        $data['gasolines'] = $gasolines;
        return $this->todo->successResponse('data', $data);
    }
    // Obteniendo el valor de venta por bomba
    public function getSale(Request $request)
    {
        try {
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'http://' . $this->station->dns . '/sales/public/record.php?bomb_id=' . $request->bomb_id);
            $contents = curl_exec($curl);
            curl_close($curl);
            if ($contents) {
                return \json_decode($contents, true);
            }
            return $this->todo->errorResponse('Intente más tarde');
        } catch (Exception $e) {
            return $this->todo->errorResponse('La ip o la bomba son incorrectos');
        }
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makeNotification(Request $request)
    {
        if ($this->station->id == $request->id_station) {
            if (Sale::where([['sale', $request->sale], ['station_id', $this->station->id]])->exists()) {
                return $this->todo->errorResponse('La venta fue registrada anteriormente');
            }
            if (($client = User::where('username', $request->membership)->first()) != null) {
                if ($request->tr_membership == "") {
                    $deposit = $client->client->deposits()->where([['status', 4], ['station_id', $this->station->id], ['balance', '>=', $request->price]])->first();
                } else {
                    if (($transmitter = User::where('username', $request->tr_membership)->first()) == null) {
                        return $this->todo->errorResponse('La membresía del receptor no esta disponible');
                    }
                    $deposit = $client->client->depositReceived->where('transmitter_id', $transmitter->client->id)->where('station_id', $this->station->id)->where('balance', '>=', $request->price)->where('status', 4)->first();
                }
                if ($deposit != null) {
                    $gasoline = Gasoline::find($request->id_gasoline);
                    $no_island = null;
                    try {
                        $no_island = $this->station->islands->where('bomb', $request->bomb_id)->first()->island;
                    } catch (Exception $e) {
                    }
                    $fields = array(
                        'app_id' => "62450fc4-bb2b-4f2e-a748-70e8300c6ddb",
                        'data' => array(
                            'id_dispatcher' => $this->dispatcher->id,
                            'sale' => $request->sale,
                            'id_gasoline' => $gasoline->id,
                            "liters" => $request->liters,
                            "price" => $request->price,
                            'id_schedule' => $this->schedule->id,
                            'id_station' => $this->station->id,
                            'id_time' => $this->time->id,
                            'no_island' => $no_island,
                            'no_bomb' => $request->bomb_id,
                            "gasoline" => $gasoline->name,
                            "estacion" => $this->station->name,
                            'ids_dispatcher' => $request->ids_dispatcher,
                            'tr_membership' => $request->tr_membership
                        ), 'contents' => array(
                            "en" => "English message from postman",
                            "es" => "Realizaste una solicitud de pago."
                        ),
                        'headings' => array(
                            "en" => "English title from postman",
                            "es" => "Pago con QR"
                        ),
                        'include_player_ids' => array("$request->ids_client"),
                    );
                    $fields = json_encode($fields);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    return $this->todo->successResponse('notification', \json_decode($response));
                }
                return $this->todo->errorResponse('Saldo insuficiente');
            }
            return $this->todo->errorResponse('Membresía no disponible');
        }
        return $this->todo->errorResponse('Estación incorrecta');
    }
    // Funcion para obtener la lista de horarios de una estacion
    public function getListSchedules()
    {
        $dataSchedules = array();
        foreach ($this->station->schedules as $schedule) {
            $data = array('id' => $schedule->id, 'name' => $schedule->name);
            array_push($dataSchedules, $data);
        }
        return $this->todo->successResponse('schedules', $dataSchedules);
    }
    // Funcion para obtener los cobros del dia
    public function getPaymentsNow()
    {
        if ($this->time != null) {
            return $this->getPayments(['time_id', $this->time->id], now()->format('Y-m-d'));
        }
        return $this->todo->errorResponse('Aun no hay registro de cobros');
    }
    // Funcion para devolver la lista de cobros por fecha
    public function getListPayments(Request $request)
    {
        return $this->getPayments(['schedule_id', $request->id_schedule], $request->date);
    }
    // Funcion para listar los cobros del depachador
    private function getPayments($array, $date)
    {
        if (count($payments = Sale::where([['dispatcher_id', $this->dispatcher->id], ['station_id', $this->station->id], $array])->whereDate('created_at', $date)->get()) > 0) {
            $dataPayment = array();
            $magna = 0;
            $premium = 0;
            $diesel = 0;
            foreach ($payments as $payment) {
                $data = array(
                    'id' => $payment->id,
                    'payment' => $payment->payment,
                    'gasoline' => $payment->gasoline->name,
                    'liters' => $payment->liters,
                    'date' => $payment->created_at->format('Y/m/d'),
                    'hour' => $payment->created_at->format('H:i:s')
                );
                array_push($dataPayment, $data);
                switch ($payment->gasoline->name) {
                    case 'Magna':
                        $magna += $payment->liters;
                        break;
                    case 'Premium':
                        $premium += $payment->liters;
                        break;
                    case 'Diésel':
                        $diesel += $payment->liters;
                        break;
                }
            }
            $info['liters_product'] = array('Magna' => $magna, 'Premium' => $premium, 'Diésel' => $diesel);
            $info['payment'] = $dataPayment;
            return $this->todo->successResponse('payments', $info);
        }
        return $this->todo->errorResponse('Aun no hay registro de cobros');
    }
}
