<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_use_token_and_logout(): void
    {
        $userId = $this->createUserWithRole('admin');

        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('data.actor.id', $userId)
            ->assertJsonPath('data.actor.roles.0', 'admin')
            ->assertJsonStructure(['data' => ['token', 'actor']]);

        $token = $loginResponse->json('data.token');

        $this->assertDatabaseCount('api_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.actor.username', 'admin');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertDatabaseCount('api_tokens', 0);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_invalid_login_is_rejected(): void
    {
        $this->createUserWithRole('admin');

        $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrong',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    private function createUserWithRole(string $roleCode): int
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'admin',
            'display_name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'code' => $roleCode,
            'name' => ucfirst($roleCode),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return $userId;
    }
}
