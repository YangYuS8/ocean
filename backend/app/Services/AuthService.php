<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(private readonly GovernanceService $governanceService) {}

    /**
     * @return array{token:string, actor:array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}}
     */
    public function login(string $username, string $password): array
    {
        $user = DB::table('users')->select(['id', 'username', 'display_name', 'password', 'status'])->where('username', $username)->first();

        if (! $user || $user->status !== 'active' || ! $user->password || ! Hash::check($password, $user->password)) {
            throw new ApiException('INVALID_CREDENTIALS', 'invalid username or password', 401);
        }

        $plainToken = Str::random(64);

        DB::table('api_tokens')->insert([
            'user_id' => (int) $user->id,
            'name' => 'spa',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(12),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'token' => $plainToken,
            'actor' => $this->governanceService->actorForUserId((int) $user->id),
        ];
    }

    public function logout(?string $plainToken): void
    {
        if ($plainToken === null || trim($plainToken) === '') {
            return;
        }

        DB::table('api_tokens')->where('token_hash', hash('sha256', $plainToken))->delete();
    }

    /**
     * @return array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}
     */
    public function actorFromBearerToken(?string $plainToken): array
    {
        if ($plainToken === null || trim($plainToken) === '') {
            throw new ApiException('UNAUTHENTICATED', 'bearer token is required', 401);
        }

        $token = DB::table('api_tokens')->where('token_hash', hash('sha256', $plainToken))->first();

        if (! $token || ($token->expires_at !== null && now()->greaterThan($token->expires_at))) {
            throw new ApiException('UNAUTHENTICATED', 'bearer token is invalid or expired', 401);
        }

        DB::table('api_tokens')->where('id', $token->id)->update([
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->governanceService->actorForUserId((int) $token->user_id);
    }
}
