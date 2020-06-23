<?php

namespace App\Http\Middleware;

use Closure;
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
            JWTAuth::parseToken()->authenticate();
            return $next($request);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            // Excepcion para la expiracion del token
            $message = 'Token expirado';
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            // Excepcion para el token invalido
            $message = 'Token invalido';
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Excepcion para JWT
            $message = 'Proporcione el token';
        }
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
