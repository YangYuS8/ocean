<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SampleService
{
    use PaginatesQueries;

    public function index(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $builder = DB::table('samples as s')->leftJoin('users as u', 'u.id', '=', 's.collector_id');

        foreach (['sample_code', 'sample_type', 'status'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('s.'.$field, $query[$field]);
            }
        }
        foreach (['inspection_task_id', 'collector_id'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('s.'.$field, (int) $query[$field]);
            }
        }
        if (!empty($query['collection_date_from'])) {
            $builder->where('s.collection_time', '>=', $query['collection_date_from'].' 00:00:00');
        }
        if (!empty($query['collection_date_to'])) {
            $builder->where('s.collection_time', '<=', $query['collection_date_to'].' 23:59:59');
        }

        $total = (clone $builder)->count();
        $rows = $builder->select(['s.*', 'u.display_name as collector_name'])
            ->orderByDesc('s.id')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'sample_code' => $row->sample_code,
            'inspection_task_id' => $row->inspection_task_id === null ? null : (int) $row->inspection_task_id,
            'sample_type' => $row->sample_type,
            'name' => $row->name,
            'status' => $row->status,
            'collection_time' => $row->collection_time,
            'location_text' => $row->location_text,
            'collector_id' => $row->collector_id === null ? null : (int) $row->collector_id,
            'collector_name' => $row->collector_name,
            'main_image' => $this->formatMainImage($row),
        ])->all();

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function store(array $payload): array
    {
        if (DB::table('samples')->where('sample_code', $payload['sample_code'])->exists()) {
            throw new ApiException('VALIDATION_ERROR', 'sample_code must be unique', 422);
        }

        if (!empty($payload['inspection_task_id']) && !DB::table('inspection_tasks')->where('id', (int) $payload['inspection_task_id'])->exists()) {
            throw new ApiException('NOT_FOUND', 'inspection task not found', 404);
        }

        if (!empty($payload['collector_id'])) {
            $this->assertUserExists((int) $payload['collector_id']);
        }

        $id = DB::table('samples')->insertGetId([
            'sample_code' => $payload['sample_code'],
            'inspection_task_id' => $payload['inspection_task_id'] ?? null,
            'sample_type' => $payload['sample_type'],
            'name' => $payload['name'] ?? null,
            'status' => 'registered',
            'collection_time' => $payload['collection_time'] ?? null,
            'location_text' => $payload['location_text'] ?? null,
            'collector_id' => $payload['collector_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'sample_code' => $payload['sample_code'],
            'status' => 'registered',
            'created_at' => DB::table('samples')->where('id', $id)->value('created_at'),
        ];
    }

    public function getStatusSnapshot(int $id): object
    {
        $sample = DB::table('samples')->select(['id', 'status'])->where('id', $id)->first();

        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }

        return $sample;
    }

    public function assertCanAcceptResult(object $sample): void
    {
        if (in_array($sample->status, ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept results in current state', 409);
        }
    }

    public function advanceToTestingWhenReceivingResult(object $sample): string
    {
        if (!in_array($sample->status, ['registered', 'received'], true)) {
            return $sample->status;
        }

        DB::table('samples')->where('id', $sample->id)->update([
            'status' => 'testing',
            'updated_at' => now(),
        ]);

        return 'testing';
    }

    public function show(int $id): array
    {
        $sample = DB::table('samples as s')
            ->leftJoin('users as cu', 'cu.id', '=', 's.collector_id')
            ->leftJoin('users as ru', 'ru.id', '=', 's.received_by')
            ->select(['s.*', 'cu.display_name as collector_name', 'ru.display_name as received_by_name'])
            ->where('s.id', $id)
            ->first();

        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }

        return [
            'id' => (int) $sample->id,
            'sample_code' => $sample->sample_code,
            'inspection_task_id' => $sample->inspection_task_id === null ? null : (int) $sample->inspection_task_id,
            'sample_type' => $sample->sample_type,
            'name' => $sample->name,
            'status' => $sample->status,
            'collection_time' => $sample->collection_time,
            'location_text' => $sample->location_text,
            'collector' => $sample->collector_id === null ? null : [
                'id' => (int) $sample->collector_id,
                'display_name' => $sample->collector_name,
            ],
            'received_by' => $sample->received_by === null ? null : [
                'id' => (int) $sample->received_by,
                'display_name' => $sample->received_by_name,
            ],
            'received_at' => $sample->received_at,
            'notes' => $sample->notes,
            'main_image' => $this->formatMainImage($sample),
            'created_at' => $sample->created_at,
            'updated_at' => $sample->updated_at,
        ];
    }

    public function storeMainImage(int $id, UploadedFile $image): array
    {
        $sample = $this->findSample($id);
        $oldPath = $sample->main_image_path;
        $path = $image->store('sample-main-images', 'public');
        $version = ((int) ($sample->main_image_version ?? 0)) + 1;
        $uploadedAt = now();

        DB::table('samples')->where('id', $id)->update([
            'main_image_path' => $path,
            'main_image_name' => $image->getClientOriginalName(),
            'main_image_mime_type' => $image->getClientMimeType(),
            'main_image_size' => $image->getSize(),
            'main_image_version' => $version,
            'main_image_uploaded_at' => $uploadedAt,
            'updated_at' => $uploadedAt,
        ]);

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $updated = $this->findSample($id);

        return [
            'sample_id' => $id,
            'main_image' => $this->formatMainImage($updated),
        ];
    }

    public function showMainImageContent(int $id): BinaryFileResponse
    {
        $sample = $this->findSample($id);

        if (!$sample->main_image_path) {
            throw new ApiException('NOT_FOUND', 'main image not found', 404);
        }

        if (!Storage::disk('public')->exists($sample->main_image_path)) {
            throw new ApiException('NOT_FOUND', 'main image content not found', 404);
        }

        return response()->file(
            Storage::disk('public')->path($sample->main_image_path),
            ['Content-Type' => $sample->main_image_mime_type ?? 'application/octet-stream']
        );
    }

    public function showImageSuggestion(int $id): array
    {
        $sample = $this->findSample($id);

        if (!$sample->main_image_path) {
            return [
                'state' => 'missing_main_image',
                'summary' => null,
                'suggestion' => null,
                'job' => null,
            ];
        }

        $jobs = DB::table('analysis_jobs')
            ->where('sample_id', $id)
            ->where('job_type', 'object_detection')
            ->orderByDesc('id')
            ->get();

        $currentVersion = (int) ($sample->main_image_version ?? 0);
        $currentVersionJobs = $jobs->filter(function ($job) use ($currentVersion) {
            return (int) Arr::get($this->decodeJson($job->params_json), 'main_image_version', 0) === $currentVersion;
        });

        $activeCurrentJob = $currentVersionJobs->first(fn ($job) => in_array($job->status, ['queued', 'running'], true));
        $latestSucceededCurrentJob = $currentVersionJobs->first(fn ($job) => $job->status === 'succeeded');

        if ($activeCurrentJob && $latestSucceededCurrentJob) {
            return [
                'state' => 'refreshing',
                'summary' => $latestSucceededCurrentJob->result_summary,
                'suggestion' => $this->decodeJson($latestSucceededCurrentJob->suggestion_json),
                'job' => [
                    'id' => (int) $activeCurrentJob->id,
                    'status' => $activeCurrentJob->status,
                    'finished_at' => $activeCurrentJob->finished_at,
                    'started_at' => $activeCurrentJob->started_at,
                    'queued_at' => $activeCurrentJob->queued_at,
                    'current_main_image_version' => $currentVersion,
                    'job_main_image_version' => $currentVersion,
                ],
            ];
        }

        $currentJob = $currentVersionJobs->first();

        if ($currentJob) {
            return $this->formatSuggestionState($currentJob, $sample, true);
        }

        $latestHistorical = $jobs->first();
        if ($latestHistorical) {
            return $this->formatSuggestionState($latestHistorical, $sample, false);
        }

        return [
            'state' => 'idle',
            'summary' => null,
            'suggestion' => null,
            'job' => null,
        ];
    }

    private function findSample(int $id): object
    {
        $sample = DB::table('samples')->where('id', $id)->first();

        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }

        return $sample;
    }

    private function formatMainImage(object $sample): ?array
    {
        if (!$sample->main_image_path) {
            return null;
        }

        return [
            'file_name' => $sample->main_image_name,
            'mime_type' => $sample->main_image_mime_type,
            'size' => $sample->main_image_size === null ? null : (int) $sample->main_image_size,
            'version' => (int) ($sample->main_image_version ?? 0),
            'uploaded_at' => $sample->main_image_uploaded_at,
            'content_url' => sprintf('/api/samples/%d/main-image/content', (int) $sample->id),
        ];
    }

    private function formatSuggestionState(object $job, object $sample, bool $isCurrent): array
    {
        $state = match ($job->status) {
            'queued', 'running' => 'running',
            'failed' => $isCurrent ? 'failed' : 'stale',
            'succeeded' => $isCurrent ? 'current' : 'stale',
            default => $isCurrent ? 'idle' : 'stale',
        };

        return [
            'state' => $state,
            'summary' => $job->result_summary,
            'suggestion' => $this->decodeJson($job->suggestion_json),
            'job' => [
                'id' => (int) $job->id,
                'status' => $job->status,
                'finished_at' => $job->finished_at,
                'started_at' => $job->started_at,
                'queued_at' => $job->queued_at,
                'current_main_image_version' => (int) ($sample->main_image_version ?? 0),
                'job_main_image_version' => (int) Arr::get($this->decodeJson($job->params_json), 'main_image_version', 0),
            ],
        ];
    }

    private function decodeJson(?string $json): mixed
    {
        if ($json === null || $json === '') {
            return null;
        }

        return json_decode($json, true);
    }

    private function assertUserExists(int $userId): void
    {
        if (!DB::table('users')->where('id', $userId)->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }
    }
}
