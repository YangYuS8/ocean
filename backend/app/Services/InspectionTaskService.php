<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Support\Facades\DB;

class InspectionTaskService
{
    use PaginatesQueries;

    public function index(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $builder = DB::table('inspection_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assigned_to');

        if (!empty($query['status'])) {
            $builder->where('t.status', $query['status']);
        }
        if (!empty($query['assigned_to'])) {
            $builder->where('t.assigned_to', (int) $query['assigned_to']);
        }
        if (!empty($query['task_type'])) {
            $builder->where('t.task_type', $query['task_type']);
        }
        if (!empty($query['planned_date_from'])) {
            $builder->where('t.planned_at', '>=', $query['planned_date_from'].' 00:00:00');
        }
        if (!empty($query['planned_date_to'])) {
            $builder->where('t.planned_at', '<=', $query['planned_date_to'].' 23:59:59');
        }
        if (!empty($query['keyword'])) {
            $keyword = '%'.$query['keyword'].'%';
            $builder->where(function ($q) use ($keyword) {
                $q->where('t.task_code', 'like', $keyword)
                    ->orWhere('t.title', 'like', $keyword)
                    ->orWhere('t.location_text', 'like', $keyword);
            });
        }

        $total = (clone $builder)->count();
        $rows = $builder->select([
                't.id', 't.task_code', 't.title', 't.task_type', 't.priority', 't.status',
                't.location_text', 't.planned_at', 't.due_at', 'u.id as assigned_to_id', 'u.display_name as assigned_to_name',
            ])
            ->orderByDesc('t.id')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'task_code' => $row->task_code,
            'title' => $row->title,
            'task_type' => $row->task_type,
            'priority' => $row->priority,
            'status' => $row->status,
            'location_text' => $row->location_text,
            'planned_at' => $row->planned_at,
            'due_at' => $row->due_at,
            'assigned_to' => $row->assigned_to_id === null ? null : [
                'id' => (int) $row->assigned_to_id,
                'display_name' => $row->assigned_to_name,
            ],
        ])->all();

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function show(int $id): array
    {
        $task = DB::table('inspection_tasks as t')
            ->leftJoin('users as au', 'au.id', '=', 't.assigned_to')
            ->leftJoin('users as cu', 'cu.id', '=', 't.created_by')
            ->select(['t.*', 'au.display_name as assigned_to_name', 'cu.display_name as created_by_name'])
            ->where('t.id', $id)
            ->first();

        if (!$task) {
            throw new ApiException('NOT_FOUND', 'inspection task not found', 404);
        }

        return [
            'id' => (int) $task->id,
            'task_code' => $task->task_code,
            'title' => $task->title,
            'description' => $task->description,
            'task_type' => $task->task_type,
            'priority' => $task->priority,
            'status' => $task->status,
            'location_text' => $task->location_text,
            'planned_at' => $task->planned_at,
            'due_at' => $task->due_at,
            'assigned_to' => $task->assigned_to === null ? null : [
                'id' => (int) $task->assigned_to,
                'display_name' => $task->assigned_to_name,
            ],
            'created_by' => $task->created_by === null ? null : [
                'id' => (int) $task->created_by,
                'display_name' => $task->created_by_name,
            ],
            'started_at' => $task->started_at,
            'submitted_at' => $task->submitted_at,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
        ];
    }

    public function start(int $id, array $payload): array
    {
        $task = DB::table('inspection_tasks')->select(['id', 'status'])->where('id', $id)->first();
        if (!$task) {
            throw new ApiException('NOT_FOUND', 'inspection task not found', 404);
        }
        if ($task->status !== 'assigned') {
            throw new ApiException('INVALID_STATE', 'inspection task cannot be started from current state', 409);
        }

        $this->assertUserExists((int) $payload['operator_id']);

        DB::table('inspection_tasks')->where('id', $id)->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'in_progress',
            'started_at' => DB::table('inspection_tasks')->where('id', $id)->value('started_at'),
        ];
    }

    public function submit(int $id, array $payload): array
    {
        $task = DB::table('inspection_tasks')->select(['id', 'status'])->where('id', $id)->first();
        if (!$task) {
            throw new ApiException('NOT_FOUND', 'inspection task not found', 404);
        }
        if ($task->status !== 'in_progress') {
            throw new ApiException('INVALID_STATE', 'inspection task cannot be submitted from current state', 409);
        }

        $this->assertUserExists((int) $payload['operator_id']);

        DB::table('inspection_tasks')->where('id', $id)->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'submitted',
            'submitted_at' => DB::table('inspection_tasks')->where('id', $id)->value('submitted_at'),
        ];
    }

    private function assertUserExists(int $userId): void
    {
        if (!DB::table('users')->where('id', $userId)->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }
    }
}
