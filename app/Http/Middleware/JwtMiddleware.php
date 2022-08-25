<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;


class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     **/
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if( !$user ) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            } // throw new Exception('User Not Found');
        } catch (Exception $e) {

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['success' => false, 'message' => 'Token Invalid'], 402);

            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                try
                {
                    $newToken = JWTAuth::parseToken()->refresh();
                    //$refreshed = JWTAuth::refresh(JWTAuth::getToken());
                    $user = JWTAuth::setToken($newToken)->toUser();
                    $response = $next($request);
                    $response->headers->set('Authorization', 'Bearer '.$newToken);
                }
                catch (JWTException $e)
                {
                    return response()->json(['success' => false, 'message' => 'Logged Out', 'code' => 401]);
                }
                // return response()->json(['success' => false, 'message' => 'token expired', 'code' => 401]);

            } else {
                if( $e->getMessage() === 'User Not Found') {
                    return response()->json(['success' => false, 'message' => 'User not found']);
                }
                return response()->json(['success' => false, 'message' => 'Authorization Token not found']);
            }
        }
        return $next($request);
    }
}