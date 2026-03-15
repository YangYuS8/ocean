<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalysisJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_job_can_start_from_queued(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'queued']);

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/start");

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

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/succeed", [
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

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/fail", [
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

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/cancel");

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

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/retry");

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
    }

    public function test_invalid_analysis_job_transition_is_rejected(): void
    {
        $jobId = $this->createAnalysisJob(['status' => 'queued']);

        $response = $this->postJson("/api/analysis-jobs/{$jobId}/succeed", [
            'result_summary' => '不应直接成功',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_STATE');
    }

    private function createAnalysisJob(array $overrides = []): int
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'analyst01',
            'display_name' => '分析员01',
            'email' => 'analyst01@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sampleId = DB::table('samples')->insertGetId([
            'sample_code' => 'SP-TEST-001',
            'sample_type' => 'water',
            'status' => 'testing',
            'collector_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
}
