<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GovernanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_identity_is_injected_and_reported_by_governance_endpoint(): void
    {
        $actorId = $this->createUserWithRole('inspector');

        $response = $this->withHeader('X-Ocean-Actor-Id', (string) $actorId)
            ->getJson('/api/governance/me');

        $response->assertOk()
            ->assertJsonPath('data.actor.id', $actorId)
            ->assertJsonPath('data.actor.roles.0', 'inspector')
            ->assertJsonPath('data.identity_strategy.header', 'X-Ocean-Actor-Id');
    }

    public function test_rbac_rejects_token_actor_without_required_permission(): void
    {
        $sampleId = $this->createSample();
        $inspectorId = $this->createUserWithRole('inspector', 'inspector-denied');
        $token = $this->createTokenForUser($inspectorId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/samples/{$sampleId}/results", [
                'result_type' => 'salinity',
                'raw_value' => ['value' => 31.2],
            ]);

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_protected_mutation_requires_token_actor(): void
    {
        $sampleId = $this->createSample();

        $response = $this->postJson("/api/samples/{$sampleId}/results", [
            'result_type' => 'salinity',
            'raw_value' => ['value' => 31.2],
            'entered_by' => $this->createUserWithRole('analyst'),
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_header_only_identity_cannot_start_inspection_task(): void
    {
        $inspectorId = $this->createUserWithRole('inspector');
        $taskId = DB::table('inspection_tasks')->insertGetId([
            'task_code' => 'IT-GOV-'.uniqid(),
            'title' => 'Governance task',
            'task_type' => 'routine_inspection',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_to' => $inspectorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('X-Ocean-Actor-Id', (string) $inspectorId)
            ->postJson("/api/inspection-tasks/{$taskId}/start", []);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_token_identity_can_start_inspection_task_and_records_audit_event(): void
    {
        $inspectorId = $this->createUserWithRole('inspector');
        $token = $this->createTokenForUser($inspectorId);
        $taskId = DB::table('inspection_tasks')->insertGetId([
            'task_code' => 'IT-GOV-'.uniqid(),
            'title' => 'Governance task',
            'task_type' => 'routine_inspection',
            'priority' => 'normal',
            'status' => 'assigned',
            'assigned_to' => $inspectorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/inspection-tasks/{$taskId}/start", []);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'inspection_task.started',
            'resource_type' => 'inspection_task',
            'resource_id' => $taskId,
            'actor_id' => $inspectorId,
            'actor_source' => 'request_header',
        ]);
    }

    public function test_worker_header_can_advance_analysis_job_without_actor_id_header(): void
    {
        $workerId = $this->createUserWithRole('worker', 'worker01');
        $jobId = DB::table('analysis_jobs')->insertGetId([
            'sample_id' => $this->createSample(),
            'job_type' => 'quality_assessment',
            'status' => 'queued',
            'queued_by' => null,
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('X-Ocean-Worker', 'ocean-python-worker')
            ->postJson("/api/analysis-jobs/{$jobId}/start");

        $response->assertOk()
            ->assertJsonPath('data.status', 'running');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'analysis_job.started',
            'resource_type' => 'analysis_job',
            'resource_id' => $jobId,
            'actor_id' => $workerId,
            'actor_source' => 'request_header',
        ]);
    }

    public function test_token_identity_replaces_payload_identity_and_records_audit_event(): void
    {
        $sampleId = $this->createSample(['status' => 'registered']);
        $analystId = $this->createUserWithRole('analyst');
        $payloadUserId = $this->createUserWithRole('inspector');
        $token = $this->createTokenForUser($analystId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/samples/{$sampleId}/results", [
                'result_type' => 'salinity',
                'raw_value' => ['value' => 31.2],
                'entered_by' => $payloadUserId,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $resultId = $response->json('data.id');

        $this->assertDatabaseHas('sample_results', [
            'id' => $resultId,
            'entered_by' => $analystId,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'sample_result.created',
            'resource_type' => 'sample_result',
            'resource_id' => $resultId,
            'actor_id' => $analystId,
            'actor_source' => 'request_header',
        ]);
    }

    public function test_legacy_payload_identity_cannot_authorize_without_header(): void
    {
        $sampleId = $this->createSample(['status' => 'registered']);
        $analystId = $this->createUserWithRole('analyst');

        $response = $this->postJson("/api/samples/{$sampleId}/results", [
            'result_type' => 'temperature',
            'entered_by' => $analystId,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_audit_events_can_be_listed(): void
    {
        $actorId = $this->createUserWithRole('analyst');

        DB::table('audit_events')->insert([
            'event_type' => 'analysis_job.cancelled',
            'resource_type' => 'analysis_job',
            'resource_id' => 123,
            'actor_id' => $actorId,
            'actor_source' => 'request_header',
            'metadata_json' => json_encode(['from_status' => 'queued']),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/audit-events?event_type=analysis_job.cancelled');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.event_type', 'analysis_job.cancelled')
            ->assertJsonPath('data.0.metadata.from_status', 'queued');
    }

    private function createUserWithRole(string $roleCode, ?string $username = null): int
    {
        DB::table('roles')->updateOrInsert(['code' => $roleCode], [
            'name' => ucfirst($roleCode),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

        $userId = DB::table('users')->insertGetId([
            'username' => $username ?? $roleCode.'-user-'.uniqid(),
            'display_name' => ucfirst($roleCode).' User',
            'email' => uniqid($roleCode.'-', true).'@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_roles')->updateOrInsert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ], []);

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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSample(array $overrides = []): int
    {
        $collectorId = $this->createUserWithRole('inspector');

        return DB::table('samples')->insertGetId(array_merge([
            'sample_code' => 'SP-GOV-'.uniqid(),
            'sample_type' => 'water',
            'status' => 'testing',
            'collector_id' => $collectorId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
