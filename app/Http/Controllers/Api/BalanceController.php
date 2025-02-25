<?php

namespace App\Http\Controllers\Api;

use App\Canje;
use App\Client;
use App\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SharedBalance;
use App\User;
use App\Deposit;
use App\Empresa;
use App\Events\MessageDns;
use App\Exchange;
use App\History;
use App\Lealtad\Ticket;
use App\Repositories\Actions;
use App\Repositories\ResponsesAndLogout;
use App\SalesQr;
use App\Station;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BalanceController extends Controller
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
    // Funcion para obtener la lista de los abonos realizados por el usuario a su cuenta
    public function getDeposits()
    {
        $payments = $this->client->deposits()->where([['status', 4], ['balance', '>', 0]])->with('station')->get();
        if ($payments->count() > 0) {
            $deposits = array();
            foreach ($payments as $payment) {
                $data['id'] = $payment->id;
                $data['balance'] = $payment->balance;
                $data['status'] = $payment->status;
                $data['station']['name'] = $payment->station->name;
                $data['station']['number_station'] = $payment->station->number_station;
                array_push($deposits, $data);
            }
            return $this->response->successResponse('payments', $deposits);
        }
        return $this->response->errorResponse('No hay abonos realizados');
    }
    // Funcion para realizar un abono a la cuenta de un usuario
    public function addBalance(Request $request)
    {
        $validator = Validator::make(
            $request->only(['deposit', 'id_station', 'image']),
            [
                'deposit' => 'required|integer|min:100',
                'id_station' => 'integer',
                'image' => 'required|image'
            ]
        );
        if ($validator->fails())
            return  $this->response->errorResponse($validator->errors());
        $request->merge([
            'client_id' => $this->client->id, 'balance' => $request->deposit,
            'image_payment' => $request->file('image')->store($this->user->username . '/' . $request->id_station, 'public'),
            'station_id' => $request->id_station, 'status' => 1
        ]);
        Deposit::create($request->all());
        return $this->response->successResponse('message', 'Solicitud realizada exitosamente');
    }
    // Funcion para devolver la membresía del cliente y la estacion
    public function useBalance(Request $request)
    {
        $deposit = $this->client->deposits()->where([['id', $request->id_payment], ['balance', '>=', $request->balance]])->first();
        if ($deposit)
            return response()->json([
                'ok' => true,
                'membership' => $this->user->username,
                'station' => [
                    'id' => $deposit->station->id,
                    'name' => $deposit->station->name,
                    'number_station' => $deposit->station->number_station,
                ],
                'balance' => $request->balance
            ]);
        return $this->response->errorResponse('Saldo insuficiente en la cuenta');
    }
    // Funcion para enviar saldo a un contacto del usuario
    public function sendBalance(Request $request)
    {
        if ($request->balance % 100 != 0 or $request->balance <= 0)
            return $this->response->errorResponse('La cantidad debe ser multiplo de $100');
        // Obteniendo el saldo disponible en la estacion correspondiente
        $payment = $this->client->deposits()->where([
            ['id', $request->id_payment], ['status', 4], ['balance', '>=', $request->balance]
        ])->first();

        if ($payment) {
            $request->merge([
                'transmitter_id' => $this->client->id, 'receiver_id' => $request->id_contact,
                'station_id' => $payment->station_id, 'status' => 5
            ]);

            SharedBalance::create($request->all());

            if ($receivedBalance = SharedBalance::where([
                ['transmitter_id', $this->client->id],
                ['receiver_id', $request->id_contact], ['station_id', $payment->station_id], ['status', 4]
            ])->first()) {
                $receivedBalance->balance += $request->balance;
                $receivedBalance->save();
            } else {
                SharedBalance::create($request->merge(['status' => 4])->all());
            }

            $payment->balance -= $request->balance;
            $payment->save();
            $notification = new Actions();
            $notification->sendNotification(Client::find($request->id_contact)->ids, 'Saldo compartido', 'Te han compartido saldo');
            return $this->response->successResponse('message', 'Saldo compartido correctamente');
        }
        return $this->response->errorResponse('Saldo insuficiente');
    }
    // Funcion que busca los abonos recibidos
    public function listReceivedPayments()
    {
        if (($balances = $this->client->depositReceived()->where([['status', 4], ['balance', '>', 0]])->get())->count() > 0) {
            $receivedBalances = [];
            foreach ($balances as $balance) {
                $data['id'] = $balance->id;
                $data['balance'] = $balance->balance;
                $data['station']['name'] = $balance->station->name;
                $data['station']['number_station'] = $balance->station->number_station;
                $data['transmitter']['membership'] = $balance->transmitter->user->username;
                $data['transmitter']['user']['name'] = $balance->transmitter->user->name;
                $data['transmitter']['user']['first_surname'] = $balance->transmitter->user->first_surname;
                $data['transmitter']['user']['second_surname'] = $balance->transmitter->user->second_surname;
                array_push($receivedBalances, $data);
            }
            return $this->response->successResponse('payments', $receivedBalances);
        }
        return $this->response->errorResponse('No hay abonos realizados');
    }
    // Funcion para devolver informacion de un saldo compartido
    public function useSharedBalance(Request $request)
    {
        $deposit = $this->client->depositReceived()
            ->where([['id', $request->id_payment], ['balance', '>=', $request->balance]])
            ->with(['station', 'transmitter', 'receiver'])->first();
        if ($deposit) {
            $station['id'] = $deposit->station->id;
            $station['name'] = $deposit->station->name;
            $station['number_station'] = $deposit->station->number_station;
            return response()->json([
                'ok' => true,
                'tr_membership' => $deposit->transmitter->user->username,
                'membership' => $deposit->receiver->user->username,
                'station' => $station,
                'balance' => $request->balance,
            ]);
        }
        return $this->response->errorResponse('Saldo insuficiente en la cuenta');
    }
    // Funcion para realizar un pago autorizado por el cliente
    public function makePayment(Request $request)
    {
        $notification = new Actions();
        if ($request->authorization == "true") {
            if ($request->balance < $request->price)
                return $this->response->errorResponse('Saldo seleccionado insuficiente');
            try {
                $request->merge([
                    'dispatcher_id' => $request->id_dispatcher, 'gasoline_id' => $request->id_gasoline,
                    'payment' => $request->price, 'schedule_id' => $request->id_schedule,
                    'station_id' => $request->id_station, 'client_id' => $this->client->id, 'time_id' => $request->id_time
                ]);
                if (!$request->tr_membership) {
                    if (($deposit = $this->client->deposits()->where([['status', 4], ['station_id', $request->id_station], ['balance', '>=', $request->price]])->first())) {
                        Sale::create($request->all());
                        $deposit->balance -= $request->price;
                        $deposit->save();
                        if ($request->id_gasoline != 3) {
                            $points = $this->addEightyPoints($this->client->id, $request->liters);
                            $this->client->points += $points;
                        }
                        $this->client->visits++;
                        $this->client->save();
                    } else {
                        return $this->response->errorResponse('Saldo insuficiente');
                    }
                } else {
                    $transmitter = User::where('username', $request->tr_membership)->first();
                    $payment = $this->client->depositReceived()->where([
                        ['transmitter_id', $transmitter->client->id], ['station_id', $request->id_station],
                        ['status', 4], ['balance', '>=', $request->price]
                    ])->first();
                    if ($payment) {
                        Sale::create($request->merge(['transmitter_id' => $transmitter->client->id])->all());
                        $payment->balance -= $request->price;
                        $payment->save();
                        $transmitter->client->points += $this->roundHalfDown($request->liters);
                        $transmitter->client->save();
                        $this->client->visits++;
                        $this->client->save();
                    } else {
                        return $this->response->errorResponse('Saldo insuficiente');
                    }
                }
                return $notification->sendNotification($request->ids_dispatcher, 'Cobro realizado con éxito', 'Pago con QR');
            } catch (Exception $e) {
                return $this->response->errorResponse('Error al registrar el cobro');
            }
        }
        return $notification->sendNotification($request->ids_dispatcher, 'Cobro cancelado', 'Pago con QR');
    }
    // Metoodo para sumar de puntos QR o formulario
    public function addPoints(Request $request)
    {
        if ($request->qr)
            $request->merge(['code' => substr($request->qr, 0, 15), 'station' => substr($request->qr, 15, 5), 'sale' => substr($request->qr, 20)]);
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|min:15',
            'station' => 'required|string|min:5',
            'sale' => 'required|string',
        ]);
        if ($validator->fails())
            return  $this->response->errorResponse($validator->errors());

        $ip = $request->ip(); // Obtener la IP del cliente
        Log::error('IP para bloquear:'. $ip);
        // Verificar si la IP está bloqueada
        if(Cache::has('blocked_ip_' . $ip)) {
            return  $this->response->errorResponse("Demasiadas solicitudes. Inténtalo de nuevo más tarde.");
        }
        Cache::put('blocked_ip_' . $ip, true, 10); // Bloquear la IP por 15 segundos
        Log::info('IP bloqueado:'. $ip .' para la venta:'.$request->sale .' - estacion: '.$request->station);

        if ($station = Station::where('number_station', $request->station)->first()) {
            $dns = 'http://' . $station->dns . '/sales/public/points.php?sale=' . $request->sale . '&code=' . $request->code;
            $saleQr = SalesQr::where([['sale', $request->sale], ['station_id', $station->id]])->first();
            if ($saleQr && $saleQr->points == 0) {
                $sale = $this->sendDnsMessage($station, $dns, $saleQr);
                if (is_string($sale))
                    return $this->response->errorResponse($sale);
                $data = $this->status_L(0, $sale, $request, $station, $this->user, $saleQr);
                if (is_string($data))
                    return $this->response->errorResponse($data);
                $saleQr->update($data->all());
                return $this->addPointsEucomb($this->user, $data->points);
            }
            if (SalesQr::where([['sale', $request->sale], ['station_id', $station->id]])->exists() || Sale::where([['sale', $request->sale], ['station_id', $station->id]])->exists() || Ticket::where([['number_ticket', $request->sale], ['id_gas', $station->id]])->exists()) {
                $scanedTicket = SalesQr::where([['sale', $request->sale], ['station_id', $station->id]])->first();
                if ($scanedTicket)
                    return $this->messageScanedTicket($scanedTicket->client_id, $this->client->id);
                $scanedTicket = Sale::where([['sale', $request->sale], ['station_id', $station->id]])->first();
                if ($scanedTicket)
                    return $this->messageScanedTicket($scanedTicket->client_id, $this->client->id);
                $scanedTicket = Ticket::where([['number_ticket', $request->sale], ['id_gas', $station->id]])->first();
                if ($scanedTicket)
                    return $this->messageScanedTicket($scanedTicket->number_usuario, $this->user->username);
                return $this->response->errorResponse('Esta venta fue registrada anteriormente');
            }
            if (count(SalesQr::where([['client_id', $this->client->id]])->whereDate('created_at', now()->format('Y-m-d'))->get()) < 4) {
                $sale = $this->sendDnsMessage($station, $dns);
                if (is_string($sale))
                    return $this->response->errorResponse($sale);
                // return $sale;
                $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                $start = $dateSale->modify('+2 minute');
                $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                $dateSale->modify('+2 minute');
                $end = $dateSale->modify('+48 hours');
                if (now() < $start)
                    return $this->response->errorResponse("Escanee su QR {$start->diff(now())->i} minutos despues de su compra");
                if (now() > $end)
                    return $this->response->errorResponse('Han pasado 24 hrs para escanear su QR');
                $data = $this->status_L(0, $sale, $request, $station, $this->user);
                if (is_string($data))
                    return $this->response->errorResponse($data);
                $qr = SalesQr::create($data->all());
                $pointsEucomb = Empresa::find(1)->double_points;
                $points = $this->addEightyPoints($this->client->id, $request->liters, $pointsEucomb, $start);
                if ($points == 0) {
                    $qr->delete();
                    $limit = $pointsEucomb * 80;
                    return $this->response->errorResponse("Ha llegado al límite de $limit puntos por día");
                } else {
                    $qr->update(['points' => $points]);
                }
                return $this->addPointsEucomb($this->user, $points);
            }
            return $this->response->errorResponse('Solo puedes validar 4 QR\'s por día');
        }
        return $this->response->errorResponse('La estación no existe. Intente con el formulario.');
    }

    // Metodo para sumar puntos QR o formulario
    public function addPointsAlvic(Request $request)
    {
        $sale = "";

        $ip = $request->ip(); // Obtener la IP del cliente
        Log::error('IP para bloquear alvic:'. $ip);
        // Verificar si la IP está bloqueada
        if(Cache::has('blocked_ip_' . $ip)) {
            return  $this->response->errorResponse("Demasiadas solicitudes. Inténtalo de nuevo más tarde.");
        }
        Cache::put('blocked_ip_' . $ip, true, 10); // Bloquear la IP por 15 segundos

        if ($request->qr)
            $request->merge(['code' => $request->qr]);


        //Si contiene station el ticket ya fue facturado
        if( isset($request->station) ){

            //Validacion de campos de un ticket ya facturado
            $validator = Validator::make($request->all(), [
            'station' => 'required|string|exists:station,number_station_alvic',
            'sale'  => 'required|string',
            'hour'  => 'required|date_format:H:i'
            ]);
            if ($validator->fails()) {return $this->response->errorResponse($validator->errors()); }

            //Se verifica la informacion con alvic para proceder a la suma de puntos
            $sale = $this->getSaleOfStationANumber( $request );
            // echo json_encode( $sale );
            Log::info(json_encode($request->all())); // Registra parametros de solicitud

        }else{//Solo cuando el ticket no esta facturado

            $validator = Validator::make($request->all(), [
                'code' => 'required|string|min:16',
                'type' => 'required|string|in:qr,form',
                'hour' => 'required|date_format:H:i'
            ],[
                'hour.date_format' => 'El formato de hora no es válido',
            ]);
            if ($validator->fails())
                return  $this->response->errorResponse($validator->errors());

            if($request->type=="form"){ //si type es form es requerido el campo payment
                $validator = Validator::make($request->all(), [
                    'payment' => 'required|string',
                ]);
                if ($validator->fails())
                    return  $this->response->errorResponse($validator->errors());
            }
            Log::info(json_encode($request->all())); // Registra parametros de solicitud



            // // Comprobar si ya existe codigo de referencia
            // if(SalesQr::where([['reference_code', trim($request->code)]])->exists())
            //     return $this->response->errorResponse('Esta venta ya fue sumada anteriormente');

            // Consultar informacion de venta desde Alvic
            // $sale = $this->getSaleOfStationA(trim($request->code), 1); //1=Get, 2=Post
            $sale = $this->getSaleOfStationA($request, 1); //1=Get, 2=Post
        }
        // echo json_encode($sale);

        if (is_string($sale))
            return $this->response->errorResponse($sale);
        // echo json_encode($sale);
        // die();
        // $sale["station"] = '00010';
        //TODO:Validacion temporal para respuesta de estacion -1
        if( $sale["station"] == "-1" ){
            if( $sale["validation"] == 409 ){
                return $this->response->errorResponse("Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.", 409);
            }
            return $this->response->errorResponse('Inténtelo más tarde');
        }else{
            if( $sale["validation"] == 409 ){
                return $this->response->errorResponse("Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.", 409);
            }
        }

        // Agregar datos al request
        $request->merge(['station'=>trim($sale['station']), 'sale'=>trim($sale['sale']), 'reference_code'=>trim($sale['code'])]);

        Log::info('IP bloqueado alvic:'. $ip .' para la venta:'.$request->sale .' - estacion: '.$request->station);

        if ($station = Station::where('number_station_alvic', $request->station)->first()) {
            if(SalesQr::where('sale', $request->sale)->where('station_id', $station->id)->exists())
                return $this->response->errorResponse('Esta venta ya fue sumada anteriormente');

            // if ($station = Station::where('number_station', $request->station)->first()) {
            // $dns = 'http://' . $station->dns . '/sales/public/points.php?sale=' . $request->sale . '&code=' . $request->code;
            $saleQr = SalesQr::where([['sale', $request->sale], ['station_id', $station->id]])->first();
            if ($saleQr && $saleQr->points == 0) {
                if ($sale['gasoline_id'] == 3) { //diesel
                    $saleQr->delete();
                    return $this->response->errorResponse('La suma de puntos no aplica para el producto diésel.');
                }
                // $sale = $this->sendDnsMessage($station, $dns, $saleQr);
                // if (is_string($sale))
                //     return $this->response->errorResponse($sale);
                $data = $this->status_L(1, $sale, $request, $station, $this->user, $saleQr);
                if (is_string($data))
                    return $this->response->errorResponse($data);
                $saleQr->update($data->all());
                return $this->addPointsEucomb($this->user, $data->points);
            }

            if (count(SalesQr::where([['client_id', $this->client->id]])->whereDate('created_at', now()->format('Y-m-d'))->get()) < 4) {
                // $sale = $this->sendDnsMessage($station, $dns);
                // if (is_string($sale))
                //     return $this->response->errorResponse($sale);
                // return $sale;
                $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                $start = $dateSale->modify('+2 minute');
                $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                $dateSale->modify('+2 minute');
                $end = $dateSale->modify('+48 hours');
                if (now() < $start)
                    return $this->response->errorResponse("Escanee su QR {$start->diff(now())->i} minutos despues de su compra");
                if (now() > $end)
                    return $this->response->errorResponse('Han pasado 24 hrs para escanear su QR');
                $data = $this->status_L(1, $sale, $request, $station, $this->user);
                if (is_string($data))
                    return $this->response->errorResponse($data);
                $qr = SalesQr::create($data->all());
                $pointsEucomb = Empresa::find(1)->double_points;
                $points = $this->addEightyPoints($this->client->id, $request->liters, $pointsEucomb, $start);
                if ($points == 0) {
                    $qr->delete();
                    $limit = $pointsEucomb * 80;
                    return $this->response->errorResponse("Ha llegado al límite de $limit puntos por día");
                } else {
                    $qr->update(['points' => $points]);
                }
                return $this->addPointsEucomb($this->user, $points);
            }
            return $this->response->errorResponse('Solo puedes validar 4 QR\'s por día');
        }
        return $this->response->errorResponse('La estación no existe. Intente con el formulario.');
    }



     // Metodo para sumar puntos QR o formulario
     public function addPointsAlvicNewSection(Request $request)
     {
         $sale = "";

         $ip = $request->ip(); // Obtener la IP del cliente
         Log::error('IP para bloquear alvic:'. $ip);
         // Verificar si la IP está bloqueada
         if(Cache::has('blocked_ip_' . $ip)) {
             return  $this->response->errorResponse("Demasiadas solicitudes. Inténtalo de nuevo más tarde.");
         }
         Cache::put('blocked_ip_' . $ip, true, 10); // Bloquear la IP por 15 segundos

         if ($request->qr)
             $request->merge(['code' => $request->qr]);


         //Si contiene station el ticket ya fue facturado
         if( isset($request->station) ){

             //Validacion de campos de un ticket ya facturado
             $validator = Validator::make($request->all(), [
             'station' => 'required|string|exists:station,number_station_alvic',
             'sale'  => 'required|string',
             'hour'  => 'required|date_format:H:i'
             ]);
             if ($validator->fails()) {return $this->response->errorResponse($validator->errors()); }

             //Se verifica la informacion con alvic para proceder a la suma de puntos
             $sale = $this->getSaleOfStationANumber( $request );
             // echo json_encode( $sale );
             Log::info(json_encode($request->all())); // Registra parametros de solicitud

         }else{//Solo cuando el ticket no esta facturado

             $validator = Validator::make($request->all(), [
                 'code' => 'required|string|min:16',
                 'type' => 'required|string|in:qr,form',
                 'hour' => 'required|date_format:H:i'
             ],[
                 'hour.date_format' => 'El formato de hora no es válido',
             ]);
             if ($validator->fails())
                 return  $this->response->errorResponse($validator->errors());

             if($request->type=="form"){ //si type es form es requerido el campo payment
                 $validator = Validator::make($request->all(), [
                     'payment' => 'required|string',
                 ]);
                 if ($validator->fails())
                     return  $this->response->errorResponse($validator->errors());
             }
             Log::info(json_encode($request->all())); // Registra parametros de solicitud



             // // Comprobar si ya existe codigo de referencia
             // if(SalesQr::where([['reference_code', trim($request->code)]])->exists())
             //     return $this->response->errorResponse('Esta venta ya fue sumada anteriormente');

             // Consultar informacion de venta desde Alvic
             // $sale = $this->getSaleOfStationA(trim($request->code), 1); //1=Get, 2=Post
             $sale = $this->getSaleOfStationA($request, 1); //1=Get, 2=Post
         }
         // echo json_encode($sale);

         if (is_string($sale))
             return $this->response->errorResponse($sale);
         // echo json_encode($sale);
         // die();
         // $sale["station"] = '00010';
         //TODO:Validacion temporal para respuesta de estacion -1
         if( $sale["station"] == "-1" ){
             if( $sale["validation"] == 409 ){
                 return $this->response->errorResponse("Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.", 409);
             }
             return $this->response->errorResponse('Inténtelo más tarde');
         }else{
             if( $sale["validation"] == 409 ){
                 return $this->response->errorResponse("Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.", 409);
             }
         }

         // Agregar datos al request
         $request->merge(['station'=>trim($sale['station']), 'sale'=>trim($sale['sale']), 'reference_code'=>trim($sale['code'])]);

         Log::info('IP bloqueado alvic:'. $ip .' para la venta:'.$request->sale .' - estacion: '.$request->station);

         if ($station = Station::where('number_station_alvic', $request->station)->first()) {
             if(SalesQr::where('sale', $request->sale)->where('station_id', $station->id)->exists())
                 return $this->response->errorResponse('Esta venta ya fue sumada anteriormente');

             // if ($station = Station::where('number_station', $request->station)->first()) {
             // $dns = 'http://' . $station->dns . '/sales/public/points.php?sale=' . $request->sale . '&code=' . $request->code;
             $saleQr = SalesQr::where([['sale', $request->sale], ['station_id', $station->id]])->first();
             if ($saleQr && $saleQr->points == 0) {
                 if ($sale['gasoline_id'] == 3) { //diesel
                     $saleQr->delete();
                     return $this->response->errorResponse('La suma de puntos no aplica para el producto diésel.');
                 }
                 // $sale = $this->sendDnsMessage($station, $dns, $saleQr);
                 // if (is_string($sale))
                 //     return $this->response->errorResponse($sale);
                 $data = $this->status_L(1, $sale, $request, $station, $this->user, $saleQr);
                 if (is_string($data))
                     return $this->response->errorResponse($data);
                 $saleQr->update($data->all());
                 return $this->addPointsEucomb($this->user, $data->points);
             }

             if (count(SalesQr::where([['client_id', $this->client->id]])->whereDate('created_at', now()->format('Y-m-d'))->get()) < 4) {
                 // $sale = $this->sendDnsMessage($station, $dns);
                 // if (is_string($sale))
                 //     return $this->response->errorResponse($sale);
                 // return $sale;
                 $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                 $start = $dateSale->modify('+2 minute');
                 $dateSale = new DateTime(substr($sale['date'], 0, 4) . '-' . substr($sale['date'], 4, 2) . '-' . substr($sale['date'], 6, 2) . ' ' . $sale['hour']);
                 $dateSale->modify('+2 minute');
                 $end = $dateSale->modify('+48 hours');
                 if (now() < $start)
                     return $this->response->errorResponse("Escanee su QR {$start->diff(now())->i} minutos despues de su compra");
                 if (now() > $end)
                     return $this->response->errorResponse('Han pasado 24 hrs para escanear su QR');
                 $data = $this->status_L(1, $sale, $request, $station, $this->user);
                 if (is_string($data))
                     return $this->response->errorResponse($data);
                 $qr = SalesQr::create($data->all());
                 $pointsEucomb = Empresa::find(1)->double_points;
                 $points = $this->addEightyPoints($this->client->id, $request->liters, $pointsEucomb, $start);
                 if ($points == 0) {
                     $qr->delete();
                     $limit = $pointsEucomb * 80;
                     return $this->response->errorResponse("Ha llegado al límite de $limit puntos por día");
                 } else {
                     $qr->update(['points' => $points]);
                 }
                 return $this->addPointsEucomb($this->user, $points);
             }
             return $this->response->errorResponse('Solo puedes validar 4 QR\'s por día');
         }
         return $this->response->errorResponse('La estación no existe. Intente con el formulario.');
     }

    // Método para realizar canjes
    public function exchange(Request $request)
    {
        $ip = $request->ip(); // Obtener la IP del cliente
        // Verificar si la IP está bloqueada
        if(Cache::has('blocked_ip_' . $ip)) {
            Log::error('Demasiadas solicitudes. Inténtalo de nuevo más tarde.');
            return  $this->response->errorResponse("Demasiadas solicitudes. Inténtalo de nuevo más tarde.");
        }
        Cache::put('blocked_ip_' . $ip, true, 10); // Bloquear la IP por 15 segundos
        Log::info('IP bloqueado:'. $ip);

        if (($user = Auth::user())->verifyRole(5)) {
            if (($station = Station::find($request->id)) != null) {
                if ($user->client->points < $station->voucher->points) {
                    return $this->response->errorResponse('El canje no se puede realizar, no cuentas con puntos suficientes');
                }
                if (Exchange::where('client_id', $user->client->id)->whereDate('created_at', now()->format('Y-m-d'))->exists()) {
                    return $this->response->errorResponse('Solo se puede realizar un canje de vale por día');
                }
                if (($range = $station->vouchers->where('status', 4)->first()) != null) {
                    $lastExchange = $station->exchanges->where('exchange', '>=', $range->min)->where('exchange', '<=', $range->max)->sortByDesc('exchange')->first();
                    $voucher = 0;
                    for ($i = $range->min; $i <= $range->max; $i++) {
                        if (!(Canje::where('conta', $i)->exists()) && !(History::where('numero', $i)->exists()) && !(Exchange::where('exchange', $i)->exists())) {
                            if ($lastExchange != null) {
                                if ($lastExchange->exchange < $i) {
                                    $voucher = $i;
                                    break;
                                }
                            } else {
                                $voucher = $i;
                                break;
                            }
                        }
                    }
                    if ($voucher == 0) {
                        return $this->response->errorResponse('Por el momento no hay vales disponibles en la estación');
                    }
                    if (($reference = $user->client->reference->first()) != null) {
                        Exchange::create(array('client_id' => $user->client->id, 'exchange' => $voucher, 'station_id' => $request->id, 'points' => $station->voucher->points, 'value' => $station->voucher->value, 'status' => 11, 'reference' => $reference->username));
                    } else {
                        Exchange::create(array('client_id' => $user->client->id, 'exchange' => $voucher, 'station_id' => $request->id, 'points' => $station->voucher->points, 'value' => $station->voucher->value, 'status' => 11));
                    }
                    $range->remaining--;
                    if ($range->remaining == 0) {
                        $range->status = 8;
                    }
                    $range->save();
                    $user->client->points -= $station->voucher->points;
                    $user->client->save();
                    return $this->response->successResponse('message', 'Recuerda presentar una identificación oficial al recoger tu vale.');
                }
                return $this->response->errorResponse('Por el momento no hay vales disponibles en la estación');
            }
            return $this->response->errorResponse('La estación no existe');
        }
        return $this->logout(JWTAuth::getToken());
    }
    // Metodo para consultar la informacion de venta de una estacion
    private function getSaleOfStation($url, $saleQr = null)
    {
        try {
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            $contents = curl_exec($curl);
            curl_close($curl);
            if ($contents) {
                $contents = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $contents);
                $sale = json_decode($contents, true);
                switch ($sale['validation']) {
                    case 2:
                        return 'El código es incorrecto. Verifique la información del ticket.';
                    case 3:
                        return 'Intente más tarde';
                    case 404:
                        return 'El id de venta no existe en la estación.';
                }
                if ($sale['gasoline_id'] == 3) {
                    if ($saleQr != null) {
                        $saleQr->delete();
                    }
                    return 'La suma de puntos no aplica para el producto diésel.';
                }
                return $sale;
            }
            return 'Intente más tarde';
        } catch (Exception $e) {
            return 'Intente más tarde';
        }
    }
    // Método para validar status L
    // $typeConn (0=Onexpo, 1=Alvic)
    private function status_L($typeConn=0, $sale, $request, $station, $user, $qr = null)
    {
        if($typeConn==0){
            if ($sale['status'] == 'L' || $sale['status'] == 'l' || $sale['status'] == 'T' || $sale['status'] == 't' || $sale['status'] == 'V' || $sale['status'] == 'v') {
                if ($qr) {
                    $qr->delete();
                }
                return 'Esta venta pertenece a otro programa de recompensas';
            }
        }

        if($typeConn==1){
            // Comprobar que status sea diferente de vacio
            if(empty($sale['status'])){
                if ($qr) {
                    $qr->delete();
                }
                return 'Esta venta pertenece a otro programa de recompensas';
            }
        }

        $request->merge($sale);
        $user->client->main->count() > 0 ? $request->merge(['main_id' => $user->client->main->first()->id]) : $request;
        $request->merge(['station_id' => $station->id, 'client_id' => $user->client->id, 'points' => $this->roundHalfDown($request->liters)]);
        if (($reference = $user->client->reference->first()) != null) {
            $request->merge(['reference' => $reference->username]);
        }
        return $request;
    }
    // Método para sumar los puntos de Eucomb
    private function addPointsEucomb($user, $points)
    {
        $user->client->main->count() > 0 ? $user->client->main->first()->client->points += $points : $user->client->points += $points;
        $user->client->visits++;
        $user->client->main->count() > 0 ? $user->client->main->first()->client->save() : $user->client->save();
        return $user->client->main->count() > 0 ? $this->response->successResponse('points', 'Haz sumado puntos a ' . $user->client->main->first()->username . ' correctamente') : $this->response->successResponse('points', 'Se han sumado sus puntos correctamente');
    }
    // Metodo para calcular puntos
    private function addEightyPoints($clientId, $liters, $pointsEucomb = 1, $saleDate="")
    {
        $points = 0;
        foreach (Sale::where([['client_id', $clientId], ['transmitter_id', null]])->whereDate('created_at', now()->format('Y-m-d'))->get() as $payment) {
            $points += $this->roundHalfDown($payment->liters);
        }
        $points += SalesQr::where([['client_id', $clientId]])->whereDate('created_at', now()->format('Y-m-d'))->sum('points');
        if($saleDate==""){
            $limit = Empresa::find(1)->double_points;
            if($limit>1) { $limit=1; $pointsEucomb=1; } //No hay fechas para la promo por default sera 1
        }else{
            // date_default_timezone_set('America/Mexico_City'); //Define hora America/Mexico_City
            $company = Empresa::find(1);
            $limit = $company->double_points;

            // Verificar si hay promocion y que la venta este dentro de las fechas validas
            $startPromo = !empty($company->start_date) ? new DateTime($company->start_date ." 00:00") : "";
            $endPromo = !empty($company->end_date) ? new DateTime($company->end_date ." 23:59") : "";
            if(!empty($startPromo) && !empty($endPromo)){
                if($saleDate >= $startPromo && $saleDate <= $endPromo){
                    $pointsEucomb = $limit;
                }else{
                    if($limit>1) { $limit=1; $pointsEucomb=1; } //Esta fuera del rango de fechas siempre sera 1
                }
            }else{
                if($limit>1) { $limit=1; $pointsEucomb=1; } //No hay promo por default sera 1
            }
        }

        if($limit>1){ // Solo aplica para puntos dobles
            $points += $this->roundHalfDown($liters); // Suma antes para verificar que la suma no sea mayor a (80 * $limit)
        }
        if ($points > (80 * $limit)) {
            $points -= $this->roundHalfDown($liters, $pointsEucomb);
            if ($points <= (80 * $limit)) {
                $points = (80 * $limit) - $points;
            } else {
                $points = 0;
            }
        } else {
            $points = $this->roundHalfDown($liters, $pointsEucomb);
            if ($points > (80 * $limit)) { //Verifica que los puntos no sean mayores a 80*limit
                $points = (80 * $limit);
            }
        }
        return $points;
    }
    // Funcion redonde de la mitad hacia abajo
    private function roundHalfDown($val, $limit = 1)
    {
        $liters = explode(".", $val);
        if (count($liters) > 1) {
            $newVal = $liters[0] . '.' . $liters[1][0];
            $newVal = round($newVal, 0, PHP_ROUND_HALF_DOWN);
        } else {
            $newVal = intval($val);
        }
        return $newVal * $limit;
    }
    // Mensaje de ticket escaneado
    private function messageScanedTicket($clientId, $saleClientId)
    {
        if ($clientId == $saleClientId) {
            return $this->response->errorResponse('Ya has escaneado este ticket, verifica tus movimientos');
        } else {
            return $this->response->errorResponse('Esta venta fue registrada por otro usuario');
        }
    }
    // Metodo para enviar un correo cuando el DNS falle
    private function sendDnsMessage($station, $dns, $saleQr = null)
    {
        $sale = '';
        if ($station->dns) {
            $sale = $this->getSaleOfStation($dns, $saleQr);
            $station->update(['fail' => null]);
        }
        if (is_string($sale)) {
            if ($station->fail == null) {
                $station->update(['fail' => now()]);
                //event(new MessageDns($station));
            }
            $diff = now()->diff($station->fail);
            if ($diff->i > 0 && $diff->i % 15 == 0) {
                $station->update(['fail' => now()]);
                //event(new MessageDns($station));
            }
        }
        return $sale;
    }
    // Metodo para cerrar sesion
    private function logout($token)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->response->errorResponse('Token invalido');
        } catch (Exception $e) {
            return $this->response->errorResponse('Token invalido');
        }
    }
    // Método temporal
    public function sumar()
    {
        $lastclient = null;
        $addedPoints = 0;
        foreach (SalesQr::where('created_at', 'like', '2021-10-09%')->orderBy('client_id', 'asc')->get() as $sale) {
            $points = $this->roundHalfDown($sale->liters);
            $tempPoints = $points;
            $points *= 2;
            if ($points == ($sale->points * 2)) {
                if ($lastclient and $lastclient == $sale->client_id) {
                    $addedPoints += $points;
                    if ($addedPoints >= 160) {
                        $subtracted = $addedPoints - 160;
                        $points -= $subtracted;
                    }
                    $tempPoints = $points / 2;
                    $sale->update(['points' => $points]);
                    $sale->client->points += $tempPoints;
                    $sale->client->save();
                } else {
                    $lastclient = $sale->client_id;
                    $addedPoints = 0;
                    $addedPoints += $points;
                    $sale->update(['points' => $points]);
                    $sale->client->points += $tempPoints;
                    $sale->client->save();
                }
            }
        }
        return response()->json(['sumados' => 'ok']);
    }

    // Metodo para consultar la informacion de venta de una estacion
    private function getSaleOfStationA($request=null, $type=1)
    {
        $code = trim($request->code);
        $hour = trim($request->hour);
        $typeA = trim($request->type);

        // $host = "https://api-multioil.digitalquo.com/api/getsaletest"; //Cambiar por el correcto
        $host = "https://gasofac.mx/AlvicFac/api_sales/getSale"; //Cambiar por el correcto
        try {
            // Get
            if($type==1){
                $code = urlencode(trim($request->code));
                $hour = urlencode(trim($request->hour));
                $typeA = urlencode(trim($request->type));
                $payment = urlencode(trim($request->payment));

                // $url = $host.'?code='.$code;
                $url = $host.'?code='.$code.'&type='.$typeA.'&hour='.$hour;
                if($request->type=="form"){ //si type es form es requerido el campo payment
                    $url = $host.'?code='.$code.'&type='.$typeA.'&hour='.$hour.'&payment='.$payment;
                }

                ini_set("allow_url_fopen", 1);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                // curl_setopt($curl, CURLOPT_ENCODING, '');
                // curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                $contents = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Obtener el código de estado HTTP
                curl_close($curl);
            }

            // Post
            if($type==2){
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $host);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                // curl_setopt($curl, CURLOPT_ENCODING, '');
                // curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                // curl_setopt($curl, CURLOPT_POSTFIELDS, array('code'=>$code));  //Habilita si es form data
                // curl_setopt($curl, CURLOPT_POSTFIELDS, '{"code": "'.$code.'"}'); // Habilita si es raw
                // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Habilita si es raw
                // curl_setopt($curl, CURLOPT_POSTFIELDS, 'code='.$code); // Habilita si es urlencode
                // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // Habilita si es urlencode

                $params = array('code'=>$code, 'type'=>$typeA, 'hour'=>$hour);
                if($request->type=="form"){ //si type es form es requerido el campo payment
                    $params = array('code'=>$code, 'type'=>$typeA, 'hour'=>$hour, 'payment'=>trim($request->payment));
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);  //Habilita si es form data

                $contents = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Obtener el código de estado HTTP
                curl_close($curl);
            }

            // Validaciones
            if($httpCode==500){
                return 'El servidor no pudo responder.';
            }

            if($httpCode==400 || $httpCode==404){
                return 'El código es incorrecto. Verifique la información del ticket.';
            }

            if($httpCode==200){
                if ($contents) {
                    $contents = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $contents);
                    $sale = json_decode($contents, true);

                    if (is_string($sale)){ return $sale; } //No continua

                    if($sale['validation']==500){
                        return 'El servidor no pudo responder.';
                    }
                    if($sale['validation']==404 && $sale['sale']!=''){
                        return 'Verifica tus datos.';
                    }
                    if($sale['validation']==400 || $sale['validation']==404){
                        return 'Esta venta no existe, verifica el texto escrito.';
                    }
                    if($sale['validation']==422){
                        return 'Solamente puedes sumar tickets que incluyan combustible en la compra.';
                    }

                    // if($sale['validation']==409){
                    //     // $array = [];
                    //     // $array["type"] = 'isMessageError409';
                    //     // $array["message"] = "Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.";
                    //     // $array["code"] = $sale["validation"] ;
                    //     // return $array;
                    //     return 'Esta venta ya fue facturada por lo cual se tiene que introducir el ticket de venta.';
                    // }

                    if(!isset($sale['station'])){
                        return 'No puede continuar la suma de puntos, falta el número de estación.';
                    }

                    if(isset($sale['no_bomb'])){
                        if(empty($sale['no_bomb'])){
                            $sale['no_bomb'] = 1;
                        }
                    }else{
                        $sale['no_bomb'] = 1;
                    }

                    if ($sale['gasoline_id'] == 3) {
                        // if ($saleQr != null) {
                        //     $saleQr->delete();
                        // }
                        return 'La suma de puntos no aplica para el producto diésel.';
                    }
                    return $sale;
                }
            }
            return 'Intente más tarde';
        } catch (Exception $e) {
            return 'Intente más tarde';
        }
    }

     /**
     * *Metodo para consultar la informacion de una veta facturada
     * @param object $request : Objeto que contiene la informacion enviada por post
     */
    private function getSaleOfStationANumber( $request = null ){
        try {
            $station = trim($request->station);
            $sale = trim($request->sale);
            $type = trim($request->type);
            $hour = trim($request->hour);

            $params = array('station'=>$station, 'sale'=>$sale, 'type'=>$type, 'hour' => $hour);
            $payment = 0.0;
            if( isset($request->payment) ){
                $payment = $request->payment;
                $params['payment'] = $payment;
            }
            $host = "https://gasofac.mx/AlvicFac/api_sales/getSaleByNumber";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $host);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // curl_setopt($curl, CURLOPT_ENCODING, '');
            // curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            // curl_setopt($curl, CURLOPT_POSTFIELDS, array('code'=>$code));  //Habilita si es form data
            // curl_setopt($curl, CURLOPT_POSTFIELDS, '{"code": "'.$code.'"}'); // Habilita si es raw
            // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Habilita si es raw
            // curl_setopt($curl, CURLOPT_POSTFIELDS, 'code='.$code); // Habilita si es urlencode
            // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // Habilita si es urlencode
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);  //Habilita si es form data

            $contents = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Obtener el código de estado HTTP
            curl_close($curl);
            // echo json_encode($contents);
            // Validaciones
            if($httpCode==500){
                return 'El servidor no pudo responder.';
            }

            if($httpCode==400 || $httpCode==404){
                return 'El código es incorrecto. Verifique la información del ticket.';
            }

            if($httpCode==200){
                if ($contents) {
                    $contents = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $contents);
                    $sale = json_decode($contents, true);

                    if (is_string($sale)){ return $sale; } //No continua

                    if($sale['validation']==500){
                        return 'El servidor no pudo responder.';
                    }
                    if($sale['validation']==404 && $sale['sale']!=''){
                        return 'Verifica tus datos.';
                    }
                    if($sale['validation']==400 || $sale['validation']==404){
                        return 'Esta venta no existe, verifica el texto escrito.';
                    }
                    if($sale['validation']==422){
                        return 'Solamente puedes sumar tickets que incluyan combustible en la compra.';
                    }
                    if(!isset($sale['station'])){
                        return 'No puede continuar la suma de puntos, falta el número de estación.';
                    }

                    if(isset($sale['no_bomb'])){
                        if(empty($sale['no_bomb'])){
                            $sale['no_bomb'] = 1;
                        }
                    }else{
                        $sale['no_bomb'] = 1;
                    }

                    if ($sale['gasoline_id'] == 3) {
                        return 'La suma de puntos no aplica para el producto diésel.';
                    }
                    return $sale;
                }
            }
            return 'Intente más tarde';
        } catch (\Throwable $th) {
            return 'Intente más tarde';
        }
    }

    // Obtener listado de estaciones
    public function getStationList(Request $request)
    {
        if (($user = Auth::user())->verifyRole(5)) {
            $stations = [];
            // $stationsCol = Station::all();
            $stationsCol = Station::where('id', '!=', 9)->get();

            foreach ($stationsCol as $key=>$item) {
                $dataStation['number_station'] = $item->number_station;
                $dataStation['name'] = $item->abrev . ' - ' . $item->name;
                $dataStation['new_conn'] = 0;
                if($item->number_station_alvic){
                    $dataStation['number_station'] = $item->number_station_alvic;
                    $dataStation['new_conn'] = 1;
                }
                array_push($stations, $dataStation);
            }

            if(count($stations)>0){
                return $this->response->successResponse('data', $stations);
            }
            return $this->response->errorResponse('No hay estaciones disponibles');
        }
        return $this->logout(JWTAuth::getToken());
    }

    // Cron para limpiar las fechas de promocion despues de 72 horas
    public function clearPromotionDates()
    {
        date_default_timezone_set('America/Mexico_City'); //Define hora America/Mexico_City
        $company = Empresa::find(1);
        $end = $company->end_date;
        if(!empty($end)){
            $endDate = new DateTime($end);
            $endDate->modify('+72 hours');
            // $hour = now()->format('H'); //Hora actual
            // $minute = now()->format('i'); //Minuto actual
            // $endDate->setTime($hour, $minute);
            $nowDate = now()->setTime(0, 0); // Establecer la hora, minutos en 0 a fecha actual

            if ($nowDate > $endDate) {
                DB::table('empresas')->where('id', 1)->update(['double_points'=>1, "start_date"=>NULL, "end_date"=>NULL, "updated_at"=>now()]);
                return $this->response->successResponse('message', 'La promoción terminó, las fechas se eliminaron.');
            }
            // dd($nowDate, $endDate);
            return $this->response->errorResponse('La promoción sigue vigente, no se permite limpiar fechas');
        }

        return $this->response->errorResponse('No hay fechas para limpiar');
    }
}
