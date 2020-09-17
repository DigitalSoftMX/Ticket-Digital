<?php

namespace App\Http\Middleware;

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
        try {
            $token = JWTAuth::getToken();
            // Valida que el token sea igual al remember token proporcionado por el login
            $user = JWTAuth::parseToken()->authenticate();
            if ($user->remember_token == $token) {
                if ($user->roles[0]->name == 'despachador' && $user->active == 0) {
                    JWTAuth::invalidate(JWTAuth::parseToken($token));
                    return $this->exceptionResponse('Usuario no autorizado');
                }
                return $next($request);
            }
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return $this->exceptionResponse('Usuario no autorizado');
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->exceptionResponse('Token expirado');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->exceptionResponse('Token invalido');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->exceptionResponse('Proporcione el token');
        } catch (Exception $e) {
            return $this->exceptionResponse('Token invalido');
        }
    }
    // Funcion para devolver error de token
    private function exceptionResponse($message)
    {
        return response()->json([
            'ok' => false,
            'message' => $message
        ]);
    }
}
