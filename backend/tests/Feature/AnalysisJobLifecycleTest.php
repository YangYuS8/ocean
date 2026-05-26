<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AnalysisJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::spy();
    }

    public function test_creating_analysis_job_enqueues_worker_payload_in_redis(): void
    {
        $sampleId = $this->createSample();

        $analystId = $this->createUser('analyst-queue', 'analyst');
        $token = $this->createTokenForUser($analystId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'quality_assessment',
            'queued_by' => $analystId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued');

        $jobId = $response->json('data.id');

        Redis::shouldHaveReceived('rpush')->once()->withArgs(function (string $queue, string $payload) use ($jobId, $sampleId) {
            $decoded = json_decode($payload, true);

            return $queue === 'ocean:analysis-jobs:queued'
                && $decoded['id'] === $jobId
                && $decoded['sample_id'] === $sampleId
                && $decoded['job_type'] === 'quality_assessment'
                && isset($decoded['queued_at']);
        });
    }

    public function test_analysis_job_can_start_from_queued(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'queued']);

        $response = $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/start");

        $response->assertOk()
            ->assertJsonPath('data.status', 'running');

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $jobId,
            'status' => 'running',
        ]);
    }

    public function test_analysis_job_can_be_marked_succeeded_from_running(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'running', 'started_at' => now()]);

        $response = $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/succeed", [
            'result_summary' => '分析完成，未发现明显异常。',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'succeeded')
            ->assertJsonPath('data.result_summary', '分析完成，未发现明显异常。');

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $jobId,
            'status' => 'succeeded',
            'result_summary' => '分析完成，未发现明显异常。',
        ]);
    }

    public function test_analysis_job_can_be_marked_failed_from_running(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'running', 'started_at' => now()]);

        $response = $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/fail", [
            'error_message' => '分析服务超时',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error_message', '分析服务超时');

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $jobId,
            'status' => 'failed',
            'error_message' => '分析服务超时',
        ]);
    }

    public function test_analysis_job_can_be_cancelled_from_queued(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'queued']);
        $analystId = $this->createUser('analyst-cancel', 'analyst');
        $token = $this->createTokenForUser($analystId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/analysis-jobs/{$jobId}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $jobId,
            'status' => 'cancelled',
        ]);
    }

    public function test_failed_analysis_job_can_be_retried_as_new_queued_record(): void
    {
        $jobId = $this->createAnalysisJob([
            'status' => 'failed',
            'params_json' => json_encode(['window' => '24h']),
            'error_message' => '分析服务超时',
            'finished_at' => now(),
        ]);
        $originalJob = DB::table('analysis_jobs')->where('id', $jobId)->first();
        $analystId = $this->createUser('analyst-retry', 'analyst');
        $token = $this->createTokenForUser($analystId);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/analysis-jobs/{$jobId}/retry");

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.sample_id', $originalJob->sample_id)
            ->assertJsonPath('data.job_type', 'quality_assessment');

        $newId = $response->json('data.id');

        $this->assertNotSame($jobId, $newId);

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $jobId,
            'status' => 'failed',
            'error_message' => '分析服务超时',
        ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $newId,
            'sample_id' => $originalJob->sample_id,
            'job_type' => 'quality_assessment',
            'status' => 'queued',
            'params_json' => json_encode(['window' => '24h']),
        ]);

        Redis::shouldHaveReceived('rpush')->once()->withArgs(function (string $queue, string $payload) use ($newId, $originalJob) {
            $decoded = json_decode($payload, true);

            return $queue === 'ocean:analysis-jobs:queued'
                && $decoded['id'] === $newId
                && $decoded['sample_id'] === (int) $originalJob->sample_id
                && $decoded['job_type'] === 'quality_assessment';
        });
    }

    public function test_invalid_analysis_job_transition_is_rejected(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'queued']);

        $response = $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/succeed", [
            'result_summary' => '不应直接成功',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_STATE');
    }

    private function createAnalysisJob(array $overrides = []): int
    {
        $userId = $this->createUser('analyst01');
        $sampleId = $this->createSample($userId);

        return DB::table('analysis_jobs')->insertGetId(array_merge([
            'sample_id' => $sampleId,
            'job_type' => 'quality_assessment',
            'status' => 'queued',
            'params_json' => null,
            'result_summary' => null,
            'error_message' => null,
            'queued_by' => $userId,
            'queued_at' => now(),
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createUser(string $username, ?string $roleCode = null): int
    {
        $userId = DB::table('users')->insertGetId([
            'username' => $username,
            'display_name' => '分析员01',
            'email' => $username.'@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($roleCode !== null) {
            $roleId = DB::table('roles')->insertGetId([
                'code' => $roleCode.'-'.uniqid(),
                'name' => ucfirst($roleCode),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('roles')->where('id', $roleId)->update(['code' => $roleCode]);
            DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId]);
        }

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

    private function withWorkerHeader(): static
    {
        $workerId = $this->createUser('worker01', 'worker');

        return $this->withHeaders([
            'Authorization' => '',
            'X-Ocean-Worker' => 'ocean-python-worker',
        ]);
    }

    private function createSample(?int $collectorId = null): int
    {
        $collectorId ??= $this->createUser('collector01');

        return DB::table('samples')->insertGetId([
            'sample_code' => 'SP-TEST-'.uniqid(),
            'sample_type' => 'water',
            'status' => 'testing',
            'collector_id' => $collectorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
