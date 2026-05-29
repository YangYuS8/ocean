<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users_and_audit_events_are_recorded(): void
    {
        $adminId = $this->createUserWithRole('admin', 'admin-user', 'admin@example.com');
        $adminToken = $this->createTokenForUser($adminId);
        $inspectorRoleId = $this->ensureRole('inspector');
        $analystRoleId = $this->ensureRole('analyst');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/users', [
                'username' => 'new-user',
                'display_name' => 'New User',
                'email' => 'new-user@example.com',
                'status' => 'active',
                'password' => 'password123',
                'roles' => ['inspector'],
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.username', 'new-user')
            ->assertJsonPath('data.roles.0', 'inspector')
            ->assertJsonPath('data.preferences.display_density', 'comfortable');

        $userId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $userId,
            'role_id' => $inspectorRoleId,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.created',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $adminId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/users?search=new-user&status=active&role=inspector')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.username', 'new-user')
            ->assertJsonPath('data.0.preferences.display_density', 'comfortable');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/users/'.$userId)
            ->assertOk()
            ->assertJsonPath('data.username', 'new-user')
            ->assertJsonPath('data.preferences.display_density', 'comfortable');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/users/'.$userId, [
                'display_name' => 'Updated User',
                'email' => 'updated-user@example.com',
                'password' => 'updated-password',
            ])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Updated User')
            ->assertJsonPath('data.email', 'updated-user@example.com');

        $this->assertTrue(Hash::check('updated-password', (string) DB::table('users')->where('id', $userId)->value('password')));

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.updated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $adminId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson('/api/users/'.$userId.'/roles', [
                'roles' => ['analyst'],
            ])
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'analyst');

        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $userId,
            'role_id' => $inspectorRoleId,
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $userId,
            'role_id' => $analystRoleId,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.roles.updated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $adminId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/users/'.$userId.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.deactivated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $adminId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/users/'.$userId.'/activate')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.activated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $adminId,
        ]);
    }

    public function test_non_admin_cannot_access_admin_user_management_endpoints(): void
    {
        $inspectorId = $this->createUserWithRole('inspector', 'inspector-user', 'inspector@example.com');
        $inspectorToken = $this->createTokenForUser($inspectorId);
        $targetUserId = $this->createUserWithRole('analyst', 'target-user', 'target@example.com');

        $this->withHeader('Authorization', 'Bearer '.$inspectorToken)
            ->getJson('/api/users')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->withHeader('Authorization', 'Bearer '.$inspectorToken)
            ->postJson('/api/users', [
                'username' => 'blocked-user',
                'display_name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'status' => 'active',
                'password' => 'password123',
                'roles' => ['inspector'],
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$inspectorToken)
            ->patchJson('/api/users/'.$targetUserId, [
                'display_name' => 'Blocked Update',
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$inspectorToken)
            ->putJson('/api/users/'.$targetUserId.'/roles', [
                'roles' => ['inspector'],
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$inspectorToken)
            ->postJson('/api/users/'.$targetUserId.'/deactivate')
            ->assertForbidden();
    }

    public function test_user_can_view_and_update_own_profile_and_settings_with_audit_events(): void
    {
        $userId = $this->createUserWithRole('analyst', 'self-user', 'self@example.com');
        $token = $this->createTokenForUser($userId);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $userId)
            ->assertJsonPath('data.roles.0', 'analyst')
            ->assertJsonPath('data.preferences.display_density', 'comfortable');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/profile', [
                'display_name' => 'Self Updated',
                'email' => 'self-updated@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Self Updated')
            ->assertJsonPath('data.email', 'self-updated@example.com');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'profile.updated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $userId,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.user_id', $userId)
            ->assertJsonPath('data.display_density', 'comfortable');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/settings', [
                'language' => 'zh-CN',
                'display_density' => 'compact',
                'default_workspace_tab' => 'analysis',
                'settings_json' => [
                    'dashboard' => ['refresh_seconds' => 30],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.language', 'zh-CN')
            ->assertJsonPath('data.display_density', 'compact')
            ->assertJsonPath('data.default_workspace_tab', 'analysis')
            ->assertJsonPath('data.settings_json.dashboard.refresh_seconds', 30);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'settings.updated',
            'resource_type' => 'user',
            'resource_id' => $userId,
            'actor_id' => $userId,
        ]);
    }

    private function ensureRole(string $roleCode): int
    {
        DB::table('roles')->updateOrInsert(['code' => $roleCode], [
            'name' => ucfirst($roleCode),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('roles')->where('code', $roleCode)->value('id');
    }

    private function createUserWithRole(string $roleCode, string $username, string $email): int
    {
        $roleId = $this->ensureRole($roleCode);

        $userId = DB::table('users')->insertGetId([
            'username' => $username,
            'display_name' => ucfirst($roleCode).' User',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        return $userId;
    }

    private function createTokenForUser(int $userId): string
    {
        $plainToken = bin2hex(random_bytes(32));

        DB::table('api_tokens')->insert([
            'user_id' => $userId,
            'name' => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }
}
