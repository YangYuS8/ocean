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
        $job = DB::table('analysis_jobs as aj')
            ->leftJoin('users as u', 'u.id', '=', 'aj.queued_by')
            ->select(['aj.*', 'u.display_name as queued_by_name'])
            ->where('aj.id', $id)
            ->first();

        if (!$job) {
            throw new ApiException('NOT_FOUND', 'analysis job not found', 404);
        }

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

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
