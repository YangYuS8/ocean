<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\GovernanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectOceanActor
{
    public function __construct(
        private readonly GovernanceService $governanceService,
        private readonly AuthService $authService
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actorIdHeader = $request->headers->get('X-Ocean-Actor-Id');

        if ($request->bearerToken()) {
            $request->attributes->set('ocean_actor', $this->authService->actorFromBearerToken($request->bearerToken()));
        } elseif ($request->headers->has('X-Ocean-Worker')) {
            $request->attributes->set('ocean_actor', $this->governanceService->workerActorFromHeader($request->headers->get('X-Ocean-Worker')));
        } elseif ($actorIdHeader !== null && trim($actorIdHeader) !== '') {
            $request->attributes->set('ocean_actor', $this->governanceService->actorFromHeader($actorIdHeader));
        }

        return $next($request);
    }
}
