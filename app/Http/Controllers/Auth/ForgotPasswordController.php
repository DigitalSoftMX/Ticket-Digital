<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;
    protected function sendResetLinkResponse($response)
    {
        return response()->json(['ok' => true, 'message' => 'Se ha enviado un enlace a su correo electrónico para restablecer su contraseña']);
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return response()->json(['ok' => false, 'message' => 'No se pudo enviar el enlace, el correo electrónico no es válido o no existe la cuenta']);
    }
}
