<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Support\Facades\DB;

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
            'created_at' => $sample->created_at,
            'updated_at' => $sample->updated_at,
        ];
    }

    private function assertUserExists(int $userId): void
    {
        if (!DB::table('users')->where('id', $userId)->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }
    }
}
