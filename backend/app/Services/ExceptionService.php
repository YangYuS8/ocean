<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    use PaginatesQueries;

    public function index(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $builder = DB::table('exceptions as e')->leftJoin('users as u', 'u.id', '=', 'e.reported_by');

        foreach (['resource_type', 'category', 'severity', 'status'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('e.'.$field, $query[$field]);
            }
        }
        foreach (['resource_id', 'reported_by'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('e.'.$field, (int) $query[$field]);
            }
        }
        if (!empty($query['created_from'])) {
            $builder->where('e.created_at', '>=', $query['created_from'].' 00:00:00');
        }
        if (!empty($query['created_to'])) {
            $builder->where('e.created_at', '<=', $query['created_to'].' 23:59:59');
        }

        $total = (clone $builder)->count();
        $rows = $builder->select(['e.*', 'u.display_name as reported_by_name'])
            ->orderByDesc('e.id')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'resource_type' => $row->resource_type,
            'resource_id' => (int) $row->resource_id,
            'category' => $row->category,
            'severity' => $row->severity,
            'title' => $row->title,
            'status' => $row->status,
            'reported_by' => $row->reported_by === null ? null : [
                'id' => (int) $row->reported_by,
                'display_name' => $row->reported_by_name,
            ],
            'created_at' => $row->created_at,
        ])->all();

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function store(array $payload): array
    {
        $this->assertResourceExists($payload['resource_type'], (int) $payload['resource_id']);
        if (!empty($payload['reported_by']) && !DB::table('users')->where('id', (int) $payload['reported_by'])->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        $id = DB::table('exceptions')->insertGetId([
            'resource_type' => $payload['resource_type'],
            'resource_id' => (int) $payload['resource_id'],
            'category' => $payload['category'],
            'severity' => $payload['severity'] ?? 'medium',
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'status' => 'open',
            'reported_by' => $payload['reported_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'open',
            'created_at' => DB::table('exceptions')->where('id', $id)->value('created_at'),
        ];
    }

    public function resolve(int $id, array $payload): array
    {
        $exception = DB::table('exceptions')->select(['id', 'status'])->where('id', $id)->first();
        if (!$exception) {
            throw new ApiException('NOT_FOUND', 'exception not found', 404);
        }
        if ($exception->status !== 'open') {
            throw new ApiException('INVALID_STATE', 'exception cannot be resolved from current state', 409);
        }
        if (!DB::table('users')->where('id', (int) $payload['resolved_by'])->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        DB::table('exceptions')->where('id', $id)->update([
            'status' => 'resolved',
            'resolved_by' => (int) $payload['resolved_by'],
            'resolved_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'resolved',
            'resolved_at' => DB::table('exceptions')->where('id', $id)->value('resolved_at'),
        ];
    }

    private function assertResourceExists(string $resourceType, int $resourceId): void
    {
        $tableMap = [
            'inspection_task' => 'inspection_tasks',
            'sample' => 'samples',
            'sample_result' => 'sample_results',
        ];

        if (!isset($tableMap[$resourceType])) {
            throw new ApiException('VALIDATION_ERROR', 'unsupported resource_type', 422);
        }

        if (!DB::table($tableMap[$resourceType])->where('id', $resourceId)->exists()) {
            throw new ApiException('NOT_FOUND', sprintf('%s not found', $resourceType), 404);
        }
    }
}
