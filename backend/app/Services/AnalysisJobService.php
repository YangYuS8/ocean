<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Support\Facades\DB;

class AnalysisJobService
{
    use PaginatesQueries;

    public function index(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $builder = DB::table('analysis_jobs as aj')->leftJoin('users as u', 'u.id', '=', 'aj.queued_by');

        foreach (['job_type', 'status'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('aj.'.$field, $query[$field]);
            }
        }
        foreach (['sample_id', 'queued_by'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('aj.'.$field, (int) $query[$field]);
            }
        }
        if (!empty($query['queued_from'])) {
            $builder->where('aj.queued_at', '>=', $query['queued_from'].' 00:00:00');
        }
        if (!empty($query['queued_to'])) {
            $builder->where('aj.queued_at', '<=', $query['queued_to'].' 23:59:59');
        }

        $total = (clone $builder)->count();
        $rows = $builder->select(['aj.*', 'u.display_name as queued_by_name'])
            ->orderByDesc('aj.id')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'sample_id' => (int) $row->sample_id,
            'job_type' => $row->job_type,
            'status' => $row->status,
            'result_summary' => $row->result_summary,
            'error_message' => $row->error_message,
            'queued_by' => $row->queued_by === null ? null : [
                'id' => (int) $row->queued_by,
                'display_name' => $row->queued_by_name,
            ],
            'queued_at' => $row->queued_at,
            'started_at' => $row->started_at,
            'finished_at' => $row->finished_at,
        ])->all();

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function store(array $payload): array
    {
        $sample = DB::table('samples')->select(['id', 'status'])->where('id', (int) $payload['sample_id'])->first();
        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }
        if (in_array($sample->status, ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept analysis jobs in current state', 409);
        }
        if (!empty($payload['queued_by']) && !DB::table('users')->where('id', (int) $payload['queued_by'])->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        $id = DB::table('analysis_jobs')->insertGetId([
            'sample_id' => (int) $payload['sample_id'],
            'job_type' => $payload['job_type'],
            'status' => 'queued',
            'params_json' => array_key_exists('params', $payload) ? json_encode($payload['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'queued_by' => $payload['queued_by'] ?? null,
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'sample_id' => (int) $payload['sample_id'],
            'job_type' => $payload['job_type'],
            'status' => 'queued',
            'queued_at' => DB::table('analysis_jobs')->where('id', $id)->value('queued_at'),
        ];
    }

    public function show(int $id): array
    {
        $job = $this->findJob($id);

        return [
            'id' => (int) $job->id,
            'sample_id' => (int) $job->sample_id,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'params' => $this->decodeJson($job->params_json),
            'result_summary' => $job->result_summary,
            'error_message' => $job->error_message,
            'queued_by' => $job->queued_by === null ? null : [
                'id' => (int) $job->queued_by,
                'display_name' => $job->queued_by_name,
            ],
            'queued_at' => $job->queued_at,
            'started_at' => $job->started_at,
            'finished_at' => $job->finished_at,
        ];
    }

    public function start(int $id): array
    {
        $job = $this->findJob($id);
        $this->assertState($job, ['queued'], 'analysis job cannot be started from current state');

        $startedAt = now();

        DB::table('analysis_jobs')->where('id', $id)->update([
            'status' => 'running',
            'started_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        return [
            'id' => $id,
            'status' => 'running',
            'started_at' => DB::table('analysis_jobs')->where('id', $id)->value('started_at'),
        ];
    }

    public function succeed(int $id, array $payload): array
    {
        $job = $this->findJob($id);
        $this->assertState($job, ['running'], 'analysis job cannot be marked succeeded from current state');

        $finishedAt = now();

        DB::table('analysis_jobs')->where('id', $id)->update([
            'status' => 'succeeded',
            'result_summary' => $payload['result_summary'] ?? null,
            'error_message' => null,
            'finished_at' => $finishedAt,
            'updated_at' => $finishedAt,
        ]);

        return [
            'id' => $id,
            'status' => 'succeeded',
            'result_summary' => DB::table('analysis_jobs')->where('id', $id)->value('result_summary'),
            'finished_at' => DB::table('analysis_jobs')->where('id', $id)->value('finished_at'),
        ];
    }

    public function fail(int $id, array $payload): array
    {
        $job = $this->findJob($id);
        $this->assertState($job, ['running'], 'analysis job cannot be marked failed from current state');

        $finishedAt = now();

        DB::table('analysis_jobs')->where('id', $id)->update([
            'status' => 'failed',
            'error_message' => $payload['error_message'] ?? null,
            'finished_at' => $finishedAt,
            'updated_at' => $finishedAt,
        ]);

        return [
            'id' => $id,
            'status' => 'failed',
            'error_message' => DB::table('analysis_jobs')->where('id', $id)->value('error_message'),
            'finished_at' => DB::table('analysis_jobs')->where('id', $id)->value('finished_at'),
        ];
    }

    public function cancel(int $id): array
    {
        $job = $this->findJob($id);
        $this->assertState($job, ['queued'], 'analysis job cannot be cancelled from current state');

        DB::table('analysis_jobs')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'cancelled',
        ];
    }

    public function retry(int $id): array
    {
        $job = DB::table('analysis_jobs')
            ->select(['id', 'sample_id', 'job_type', 'status', 'params_json', 'queued_by'])
            ->where('id', $id)
            ->first();

        if (!$job) {
            throw new ApiException('NOT_FOUND', 'analysis job not found', 404);
        }

        if ($job->status !== 'failed') {
            throw new ApiException('INVALID_STATE', 'analysis job can only be retried from failed state', 409);
        }

        $sample = DB::table('samples')->select(['id', 'status'])->where('id', (int) $job->sample_id)->first();
        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }
        if (in_array($sample->status, ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept analysis jobs in current state', 409);
        }

        $newId = DB::table('analysis_jobs')->insertGetId([
            'sample_id' => (int) $job->sample_id,
            'job_type' => $job->job_type,
            'status' => 'queued',
            'params_json' => $job->params_json,
            'queued_by' => $job->queued_by,
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $newId,
            'sample_id' => (int) $job->sample_id,
            'job_type' => $job->job_type,
            'status' => 'queued',
            'queued_at' => DB::table('analysis_jobs')->where('id', $newId)->value('queued_at'),
        ];
    }

    private function findJob(int $id): object
    {
        $job = DB::table('analysis_jobs as aj')
            ->leftJoin('users as u', 'u.id', '=', 'aj.queued_by')
            ->select(['aj.*', 'u.display_name as queued_by_name'])
            ->where('aj.id', $id)
            ->first();

        if (!$job) {
            throw new ApiException('NOT_FOUND', 'analysis job not found', 404);
        }

        return $job;
    }

    private function assertState(object $job, array $allowedStates, string $message): void
    {
        if (!in_array($job->status, $allowedStates, true)) {
            throw new ApiException('INVALID_STATE', $message, 409);
        }
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
