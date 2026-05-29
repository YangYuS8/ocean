<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SampleImageSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_main_image_can_be_uploaded_and_returned_in_detail(): void
    {
        Storage::fake('public');

        [$sampleId, $userId] = $this->createSample();
        $token = $this->createTokenForUser($userId);

        $uploadResponse = $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('main-image.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ]);

        $uploadResponse->assertCreated()
            ->assertJsonPath('data.main_image.version', 1)
            ->assertJsonPath('data.main_image.file_name', 'main-image.jpg');

        $detailResponse = $this->getJson("/api/samples/{$sampleId}");

        $detailResponse->assertOk()
            ->assertJsonPath('data.main_image.version', 1)
            ->assertJsonPath('data.main_image.file_name', 'main-image.jpg');

        $sample = DB::table('samples')->where('id', $sampleId)->first();

        $this->assertNotNull($sample->main_image_path);
        $this->assertTrue(Storage::disk('public')->exists((string) $sample->main_image_path));
    }

    public function test_object_detection_job_uses_current_main_image_params(): void
    {
        Storage::fake('public');

        [$sampleId, $userId] = $this->createSample();
        $token = $this->createTokenForUser($userId);

        $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('detector.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.job_type', 'object_detection')
            ->assertJsonPath('data.status', 'queued');

        $job = DB::table('analysis_jobs')->where('id', $response->json('data.id'))->first();
        $params = json_decode($job->params_json ?? 'null', true);

        $this->assertSame(1, $params['main_image_version'] ?? null);
        $this->assertSame('detector.jpg', $params['main_image_name'] ?? null);
        $this->assertNotEmpty($params['main_image_path'] ?? null);
    }

    public function test_duplicate_active_object_detection_job_for_same_main_image_is_rejected(): void
    {
        Storage::fake('public');

        [$sampleId, $userId] = $this->createSample();
        $token = $this->createTokenForUser($userId);

        $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('detector.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ])->assertCreated();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_image_suggestion_reports_current_and_stale_states(): void
    {
        Storage::fake('public');

        [$sampleId, $userId] = $this->createSample();
        $token = $this->createTokenForUser($userId);

        $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('first.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $createJobResponse = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ])->assertCreated();

        $jobId = $createJobResponse->json('data.id');

        $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/start")->assertOk();
        $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$jobId}/succeed", [
            'result_summary' => '检测到 scallop x2, starfish x1',
            'suggestion' => [
                'has_findings' => true,
                'counts' => [
                    'scallop' => 2,
                    'starfish' => 1,
                ],
                'confidence_summary' => [
                    'top_score' => 0.91,
                ],
            ],
        ])->assertOk();

        $currentResponse = $this->getJson("/api/samples/{$sampleId}/image-suggestion");
        $currentResponse->assertOk()
            ->assertJsonPath('data.state', 'current')
            ->assertJsonPath('data.summary', '检测到 scallop x2, starfish x1')
            ->assertJsonPath('data.suggestion.has_findings', true)
            ->assertJsonPath('data.suggestion.counts.scallop', 2);

        $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('second.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $staleResponse = $this->getJson("/api/samples/{$sampleId}/image-suggestion");
        $staleResponse->assertOk()
            ->assertJsonPath('data.state', 'stale')
            ->assertJsonPath('data.summary', '检测到 scallop x2, starfish x1');
    }

    public function test_image_suggestion_keeps_previous_summary_visible_while_refreshing(): void
    {
        Storage::fake('public');

        [$sampleId, $userId] = $this->createSample();
        $token = $this->createTokenForUser($userId);

        $this->withHeader('Authorization', 'Bearer '.$token)->post("/api/samples/{$sampleId}/main-image", [
            'image' => UploadedFile::fake()->create('refresh.jpg', 120, 'image/jpeg'),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $firstJobId = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ])->json('data.id');

        $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$firstJobId}/start")->assertOk();
        $this->withWorkerHeader()->postJson("/api/analysis-jobs/{$firstJobId}/succeed", [
            'result_summary' => '检测到 scallop x1',
            'suggestion' => [
                'has_findings' => true,
                'counts' => ['scallop' => 1],
            ],
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/analysis-jobs', [
            'sample_id' => $sampleId,
            'job_type' => 'object_detection',
            'queued_by' => $userId,
        ])->assertCreated();

        $response = $this->getJson("/api/samples/{$sampleId}/image-suggestion");

        $response->assertOk()
            ->assertJsonPath('data.state', 'refreshing')
            ->assertJsonPath('data.summary', '检测到 scallop x1')
            ->assertJsonPath('data.suggestion.counts.scallop', 1);
    }

    private function createSample(): array
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'sample-owner',
            'display_name' => 'Sample Owner',
            'email' => 'sample-owner@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['analyst' => 'Analyst', 'inspector' => 'Inspector'] as $roleCode => $roleName) {
            DB::table('roles')->updateOrInsert(['code' => $roleCode], [
                'name' => $roleName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('user_roles')->updateOrInsert([
                'user_id' => $userId,
                'role_id' => DB::table('roles')->where('code', $roleCode)->value('id'),
            ], []);
        }

        $sampleId = DB::table('samples')->insertGetId([
            'sample_code' => 'SP-IMG-001',
            'sample_type' => 'benthic',
            'status' => 'registered',
            'collector_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$sampleId, $userId];
    }

    private function withWorkerHeader(): static
    {
        DB::table('users')->updateOrInsert(['username' => 'worker01'], [
            'display_name' => 'Worker 01',
            'email' => 'worker01@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('roles')->updateOrInsert(['code' => 'worker'], [
            'name' => 'Worker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->updateOrInsert([
            'user_id' => DB::table('users')->where('username', 'worker01')->value('id'),
            'role_id' => DB::table('roles')->where('code', 'worker')->value('id'),
        ], []);

        return $this->withHeaders([
            'Authorization' => '',
            'X-Ocean-Worker' => 'ocean-analysis-worker',
        ]);
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
