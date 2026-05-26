<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SampleResultFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_results_list_response_includes_meta(): void
    {
        [$sampleId, $userId] = $this->createSample(['status' => 'testing']);

        DB::table('sample_results')->insert([
            [
                'sample_id' => $sampleId,
                'result_type' => 'salinity',
                'status' => 'draft',
                'entered_by' => $userId,
                'entered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sample_id' => $sampleId,
                'result_type' => 'temperature',
                'status' => 'draft',
                'entered_by' => $userId,
                'entered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson("/api/samples/{$sampleId}/results?page=1&page_size=1");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.page_size', 1)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_creating_sample_result_advances_registered_sample_to_testing(): void
    {
        [$sampleId, $userId] = $this->createSample(['status' => 'registered']);
        $token = $this->createTokenForUser($userId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson("/api/samples/{$sampleId}/results", [
            'result_type' => 'salinity',
            'raw_value' => ['value' => 31.2, 'unit' => 'ppt'],
            'entered_by' => $userId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('samples', [
            'id' => $sampleId,
            'status' => 'testing',
        ]);
    }

    public function test_creating_sample_result_for_invalid_sample_returns_conflict(): void
    {
        [$sampleId, $userId] = $this->createSample(['status' => 'invalid']);
        $token = $this->createTokenForUser($userId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson("/api/samples/{$sampleId}/results", [
            'result_type' => 'salinity',
            'raw_value' => ['value' => 31.2, 'unit' => 'ppt'],
            'entered_by' => $userId,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_STATE');

        $this->assertDatabaseMissing('sample_results', [
            'sample_id' => $sampleId,
            'result_type' => 'salinity',
        ]);
    }

    public function test_database_seeder_provides_core_chain_sample_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $inspectionTaskId = DB::table('inspection_tasks')->where('task_code', 'IT-20260311-001')->value('id');
        $sampleId = DB::table('samples')->where('sample_code', 'SP-20260311-001')->value('id');
        $sampleResultId = DB::table('sample_results')
            ->where('sample_id', $sampleId)
            ->where('result_type', 'salinity')
            ->value('id');

        $this->assertNotNull($inspectionTaskId);
        $this->assertNotNull($sampleId);
        $this->assertNotNull($sampleResultId);

        $this->assertDatabaseHas('samples', [
            'id' => $sampleId,
            'inspection_task_id' => $inspectionTaskId,
            'status' => 'testing',
        ]);

        $this->assertDatabaseHas('exceptions', [
            'resource_type' => 'sample_result',
            'resource_id' => $sampleResultId,
            'category' => 'threshold_alert',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'quality_assessment',
            'status' => 'queued',
        ]);
    }

    private function createSample(array $sampleOverrides = []): array
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'sample-result-user-'.uniqid(),
            'display_name' => 'Sample Result User',
            'email' => uniqid('sample-result-user-', true).'@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'code' => 'analyst-'.uniqid(),
            'name' => 'Analyst',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('roles')->where('id', $roleId)->update(['code' => 'analyst']);
        DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId]);

        $sampleId = DB::table('samples')->insertGetId(array_merge([
            'sample_code' => 'SP-RESULT-'.uniqid(),
            'sample_type' => 'water',
            'status' => 'registered',
            'collector_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $sampleOverrides));

        return [$sampleId, $userId];
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
