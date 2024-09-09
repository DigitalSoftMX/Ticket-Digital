<?php

namespace App\Repositories;

use Exception;

class Actions
{
    public function sendNotification($ids, $body, $title = 'Pago con QR', $data = null)
    {
        try {
            $bearerToken = 'ya29.c.c0ASRK0Gb0dq1xmu8DVNVlTkk7Bl2IQ1thUggaewTCqEqTRjY_WF6a-JWoGGiQBdPsryE4N1xwZH96vXQWjFxCnKtLG-2xZfpN20WDc1KvhY7R1V45C0h-lBp7UtbTTmg2j19NVKvCT3Z7Vr9oICo1xRSScmNuDOe8JbsoFVksuTFwpfMMUguW38MoB_QMRpFACeH1Se3THozbmESBLVu_5JuSZKxzuVbtWU2_-cqv2hxLke5_UCYvCOSP49KRSFYhFz5G9n8663PKOBwPkM3H-Xbj0HC2fF0e2tOHfPaQg7LaZgJ2Vpn6PcECQcGOZmb_isnDRpZ9Me6s9D_Okeq8c-B0zVF7GLrMEBmgS2_2CkGXrkxPZ969oqcFT385AWt-w5-iz0y708FfiUQb4-mro5Y11ne827gxcFq82Y561V5YhM_ZJq2c0Xgo1b7VU727BWRUwVkl4yx_v6ezF_nn6XfhX2geRoeXdbbnqnbxvrSItgoUyStuU0QsRiMaupSSd_i-mXeOYd6i77Yr12bxw2YpJ4-J_hJxi_xh85gwjuXcX0ma6u2J02kl7iFRt-57pIqUFiftptRfcWtrrrn799zFiks3SV47Udfl8_MyJtucvWYr1Je3aS-hBogg2r2rWVhyuJcWb2t55lScc0_yrfo3M6IQ9o4qJ27nF9ZthnIh3Z2xeJeJUJYJVxWJhf3p9856isv5U7JV1dfQlI0SrIWOBk-UnlmYxJpqfWcruo8lUjZxymnubw61iimrwwb6no-vw-mq5hbpuR1xe_fYmpyb_0B9OSXX0J80p5q8zFrjVf5njwBoI_XklkY9jfYiFrfI_v1Rl78lpn2wvtmhzVbj6RSOJJQqotv9bd4tZZFtupejwM5gqZ3u7SRh8Ix0uwFI1Ffhid46SyOa-dYji1dVFkowJed_B9bypsuVI2QUY1kpawQS5pXwyRbW4g-t9cMaB8YUXVWQ_q_FY-q8lIIuIjt3Qt_vOjZItwmUeQFXj3s39mf3J0j'; //$this->getBearerToken();

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
