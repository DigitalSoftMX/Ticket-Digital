<?php

namespace App\Repositories;

use Exception;

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
        $url = 'http://api-onexpo.digitalquo.com/';

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
}
