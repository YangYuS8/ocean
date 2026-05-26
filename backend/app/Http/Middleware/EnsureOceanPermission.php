<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\GovernanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOceanPermission
{
    public function __construct(private readonly GovernanceService $governanceService) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $source = 'any'): Response
    {
        $actor = $request->attributes->get('ocean_actor');

        if (! is_array($actor)) {
            throw new ApiException('UNAUTHENTICATED', 'actor identity header is required for this action', 401);
        }

        if ($source === 'token' && ! $request->bearerToken()) {
            throw new ApiException('UNAUTHENTICATED', 'bearer token is required', 401);
        }

        if ($source === 'worker' && ! $request->headers->has('X-Ocean-Worker')) {
            throw new ApiException('UNAUTHENTICATED', 'worker identity header is required', 401);
        }

        if (! $this->governanceService->can($actor['roles'] ?? [], $permission)) {
            throw new ApiException('FORBIDDEN', 'actor does not have permission for this action', 403);
        }

        return $next($request);
    }
}
