<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Support\ActorContext;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ActorContext $actorContext
    ) {}

    public function login(LoginRequest $request)
    {
        return ApiResponse::success($this->authService->login(
            $request->validated('username'),
            $request->validated('password')
        ));
    }

    public function me()
    {
        return ApiResponse::success([
            'actor' => $this->actorContext->actor(),
        ]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->bearerToken());

        return ApiResponse::success(['revoked' => true]);
    }
}
