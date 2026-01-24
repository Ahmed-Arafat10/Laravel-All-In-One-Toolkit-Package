<?php

namespace AhmedArafat\AllInOne\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use AhmedArafat\AllInOne\Traits\JsonApiResponser;

class JwtMiddleware
{
    use JsonApiResponser;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param string $guard
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $guard = 'user'): Response
    {
        try {
            Auth::shouldUse($guard);
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return $this->jsonMessage(__('Unauthenticated user'), 401);
            }
        } catch (TokenInvalidException) {
            return $this->jsonMessage(__('Token is invalid'), 401);
        } catch (TokenExpiredException) {
            return $this->jsonMessage(__('Token has expired'), 401);
        } catch (Throwable) {
            return $this->jsonMessage(__('Unauthenticated user'), 401);
        }
        return $next($request);
    }
}
