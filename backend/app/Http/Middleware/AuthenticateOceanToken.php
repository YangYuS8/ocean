<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOceanToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $source = 'any'): Response
    {
        if (! is_array($request->attributes->get('ocean_actor'))) {
            throw new ApiException('UNAUTHENTICATED', 'login is required', 401);
        }

        if ($source === 'token' && ! $request->bearerToken()) {
            throw new ApiException('UNAUTHENTICATED', 'bearer token is required', 401);
        }

        if ($source === 'worker' && ! $request->headers->has('X-Ocean-Worker')) {
            throw new ApiException('UNAUTHENTICATED', 'worker identity header is required', 401);
        }

        return $next($request);
    }
}
