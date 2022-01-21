<?php

namespace App\Repositories;

use Exception;

class Actions
{
    public function sendNotification($ids, $body, $title = 'Pago con QR', $data = null)
    {
        try {
            $fields = array(
                'to' => $ids,
                'notification' =>
                array(
                    'title' => $title,
                    'body' => $body
                ),
                "priority" => "high",
            );

            if ($data)
                $fields['data'] = $data;

            // TODO el key debe estar en el .env igual que el url
            $headers = array('Authorization: key=AAAAU326I1A:APA91bGEnntzkki0Y89cJFY3Chj0cO_pnw4j6PfL_XMCreHU_VzQSH_oIi_QHwDPYtat3g5F6xCwQDikAxErJu6orWQaOzxUuIPIRR8RHTGUElA3QMNBtUf540YZ_vgDK-4iv24K5u8v', 'Content-Type: application/json');
            $url = 'https://fcm.googleapis.com/fcm/send';
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
}
