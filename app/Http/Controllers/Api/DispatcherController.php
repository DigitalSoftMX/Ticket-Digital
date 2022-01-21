<?php

namespace App\Http\Controllers\Api;

use App\Sale;
use App\Gasoline;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\RegisterTime;
use App\Repositories\Actions;
use App\Repositories\ResponsesAndLogout;
use App\Schedule;
use App\User;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class DispatcherController extends Controller
{
    private $user, $dispatcher, $station, $time, $schedule, $response;
    public function __construct(ResponsesAndLogout $response)
    {
        $this->response = $response;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->roles->first()->id != 4) {
            $this->response->logout(JWTAuth::getToken());
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
        $payments = $this->time ? Sale::whereDate('created_at', now()->format('Y-m-d'))
            ->where([
                ['dispatcher_id', $this->dispatcher->id], ['station_id', $this->station->id],
                ['time_id', $this->time->id]
            ])->get() : [];
        $totalPayment = $this->time ? $payments->sum('payment') : 0;
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
        return $this->response->successResponse('user', $data);
    }
    // Registro de inicio de turno y termino de turno
    public function startEndTime(Request $request)
    {
        switch ($request->time) {
            case 'true':
                if ($this->time) {
                    if ($this->time->status == 6)
                        return $this->response->errorResponse('Finalice el turno actual para iniciar otro');
                }
                RegisterTime::create([
                    'dispatcher_id' => $this->dispatcher->id, 'station_id' => $this->station->id, 'schedule_id' => $this->schedule->id, 'status' => 6
                ]);
                return $this->response->successResponse('message', 'Inicio de turno registrado');
            case 'false':
                if ($this->time) {
                    $this->time->update(['status' => 8]);
                    return $this->response->successResponse('message', 'Fin de turno registrado');
                }
                return $this->response->errorResponse('Turno no registrado');
        }
        return $this->response->errorResponse('Registro no valido');
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
        return $this->response->successResponse('data', $data);
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
            return $this->response->errorResponse('Intente más tarde');
        } catch (Exception $e) {
            return $this->response->errorResponse('La ip o la bomba son incorrectos');
        }
    }
    // Funcion para realizar el cobro hacia un cliente
    public function makeNotification(Request $request)
    {
        if (!$this->time)
            return $this->response->errorResponse('Debe iniciar su turno');

        if ($request->balance < $request->price)
            return $this->response->errorResponse('Saldo seleccionado insuficiente');

        if ($this->station->id != $request->id_station)
            return $this->response->errorResponse('Estación incorrecta');

        if (Sale::where([['sale', $request->sale], ['station_id', $this->station->id]])->exists())
            return $this->response->errorResponse('La venta fue registrada anteriormente');

        if ($client = User::where('username', $request->membership)->first()) {
            if (!$request->tr_membership) {
                $deposit = $client->client->deposits()
                    ->where([
                        ['status', 4], ['station_id', $this->station->id],
                        ['balance', '>=', $request->price]
                    ])->first();
            } else {
                if (!($transmitter = User::where('username', $request->tr_membership)->first())) {
                    return $this->response->errorResponse('La membresía del receptor no esta disponible');
                }
                $deposit = $client->client->depositReceived()
                    ->where([
                        ['transmitter_id', $transmitter->client->id], ['station_id', $this->station->id],
                        ['balance', '>=', $request->price], ['status', 4]
                    ])->first();
            }


            if ($deposit) {
                $gasoline = Gasoline::find($request->id_gasoline);
                $no_island = null;
                try {
                    $no_island = $this->station->islands->where('bomb', $request->bomb_id)->first()->island;
                } catch (Exception $e) {
                }
                $data = array(
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
                    'tr_membership' => $request->tr_membership,
                    'balance' => $request->balance,
                );
                $notification = new Actions();
                $response = $notification->sendNotification(
                    $request->ids_client,
                    'Realizaste una solicitud de pago.',
                    'Pago con QR',
                    $data
                );
                return $this->response->successResponse('notification', $response);
            }

            $notification = new Actions();
            $response = $notification->sendNotification(
                $request->ids_client,
                'Saldo insuficiente en la cuenta',
                'Pago con QR'
            );
            return $this->response->successResponse('notification', $response);

            return $this->response->errorResponse('Saldo insuficiente en la cuenta');
        }
        return $this->response->errorResponse('Membresía no disponible');
    }
    // Funcion para obtener la lista de horarios de una estacion
    public function getListSchedules()
    {
        $dataSchedules = array();
        foreach ($this->station->schedules as $schedule) {
            $data = array('id' => $schedule->id, 'name' => $schedule->name);
            array_push($dataSchedules, $data);
        }
        return $this->response->successResponse('schedules', $dataSchedules);
    }
    // Funcion para obtener los cobros del dia
    public function getPaymentsNow()
    {
        if ($this->time)
            return $this->getPayments(['time_id', $this->time->id], now()->format('Y-m-d'));
        return $this->response->errorResponse('Aun no hay registro de cobros');
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
            return $this->response->successResponse('payments', $info);
        }
        return $this->response->errorResponse('Aun no hay registro de cobros');
    }
}
