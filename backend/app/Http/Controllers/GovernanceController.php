<?php

namespace App\Http\Controllers;

use App\Services\GovernanceService;
use App\Support\ActorContext;
use App\Support\ApiResponse;

class GovernanceController extends Controller
{
    public function __construct(
        private readonly ActorContext $actorContext,
        private readonly GovernanceService $governanceService
    ) {}

    public function me()
    {
        return ApiResponse::success([
            'actor' => $this->actorContext->actor(),
            'identity_strategy' => [
                'header' => 'X-Ocean-Actor-Id',
                'fallback' => 'legacy payload identity fields remain accepted during the v1.4 transition',
            ],
        ]);
    }

    public function roles()
    {
        return ApiResponse::success([
            'roles' => $this->governanceService->roleCatalog(),
        ]);
    }
}
