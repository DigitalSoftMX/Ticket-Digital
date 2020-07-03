<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $message = '';
        try {
            // Verifica la validacion del token
            $token = JWTAuth::getToken();
            $user = User::find(JWTAuth::parseToken()->authenticate()->id);
            if ($user->remember_token == $token) {
                JWTAuth::parseToken()->authenticate();
                return $next($request);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Token invalido'
                ]);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            // Excepcion para la expiracion del token
            $message = 'Token expirado';
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // Excepcion para el token invalido
            $message = 'Token invalido';
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Excepcion para JWT
            $message = 'Proporcione el token';
        } catch (Exception $e) {
            $message = 'Token invalido';
        }
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
