<?php

namespace App\Http\Middleware;

use App\Http\Response\BaseApiResponse;
use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class AuthWithAdmin extends BaseMiddleware
{
    use BaseApiResponse;

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
            $user = JWTAuth::parseToken()->authenticate();

            if($user->is_admin == 1) {
                return $next($request);
            } else {
                return $this->responseForbidden('You must be Admin to access this end point');
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return $this->responseForbidden('Token is Invalid');
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return $this->responseForbidden('Token is Expired');
            }else{
                return $this->responseForbidden('Authorization Token not found');
            }
        }
    }
}
