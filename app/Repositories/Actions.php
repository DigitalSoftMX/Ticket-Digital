<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class Actions
{
    public function sendNotification($ids, $body, $title = 'Pago con QR', $data = null)
    {
        try {
            $bearerToken = $this->getBearerToken();

            $fields = array(
                'token' => $ids,
                'notification' =>
                array(
                    'title' => $title,
                    'body' => $body
                ),
                // "priority" => "high",
            );

            if ($data)
                $fields['data'] = $data;

            $fields = array('message'=>$fields);

            // TODO el key debe estar en el .env igual que el url
            $headers = array(
                'Authorization: Bearer '. $bearerToken, // Reemplaza con tu Bearer Token
                'Content-Type: application/json'
            );
            $url = 'https://fcm.googleapis.com/v1/projects/eucomb/messages:send';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            // return json_decode($result, true);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getBearerToken()
    {
        // URL para obtener el token
        $url = 'https://api-firebase.digitalquo.net/?app=1';

        // Iniciar cURL para la solicitud GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Ejecutar la solicitud y obtener la respuesta
        $response = curl_exec($ch);
        curl_close($ch);

        // Decodificar el JSON de la respuesta
        $data = json_decode($response, true);

        return $data['token'] ?? null;
    }

    // Notificar por whatsapp
    public function notificationByWhatsapp($phone="", $body="", $imageUrl=""){
        // Lalo
        // $token = 'WA5KeBGQqd72AI3dLaAfLaQHaQT8PLK5noRvxnQp71f8327b';
        // $instanceID = '22451';
        $token = 'C67gVZ0EOS5HRci6CJ7drJAElc6I3NoN5Gl431zk181259df';
        $instanceID = '22969';
        $url = "https://waapi.app/api/v1/instances/".$instanceID."/client/action/send-message"; //Waapi
        // $url = "https://waapi.app/api/v1/instances/".$instanceID."/client/action/send-media"; //Waapi

        // Wappi
        $params=array(
            'chatId' => "521".$phone."@c.us",
            'message' => $body,
            // 'mediaUrl' => $imageUrl,
            // 'mediaCaption' => $body,
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($params), // Encode data as JSON
            CURLOPT_HTTPHEADER => array(  //Con Waapi
                'accept: application/json',
                'authorization: Bearer '.$token, // Use the token variable
                'content-type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if($err){
            return false;
        } else {
            return true;
        }
    }

    // Envío de correos electronicos por Brevo
    public static function notificationByEmail($data){
        // Renderizar el contenido del correo
        $htmlContent = View::make($data['view'], [
            'data' => $data
        ])->render();
        // Log::info($htmlContent); exit;

        $apiKey = getenv("BREVO_KEY");
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $apiKey,
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Eucomb',
                'email' => 'lealtad@eucombgasolineras.mx',
            ],
            'to' => [
                [
                    'email' => $data['email'],
                ],
            ],
            'subject' => $data['subject'],
            'htmlContent' => $htmlContent,
        ]);

        if ($response->successful()) {
            return true;
        }else {
            return false;
        }
    }
}
