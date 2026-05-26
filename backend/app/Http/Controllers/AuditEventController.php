<?php

namespace App\Http\Controllers;

use App\Services\Concerns\PaginatesQueries;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditEventController extends Controller
{
    use PaginatesQueries;

    public function index(Request $request)
    {
        [$page, $pageSize, $offset] = $this->pagination($request->query());

        $builder = DB::table('audit_events as ae')
            ->leftJoin('users as u', 'u.id', '=', 'ae.actor_id');

        foreach (['event_type', 'resource_type', 'actor_source'] as $field) {
            if ($request->query($field)) {
                $builder->where('ae.'.$field, $request->query($field));
            }
        }

        foreach (['resource_id', 'actor_id'] as $field) {
            if ($request->query($field)) {
                $builder->where('ae.'.$field, (int) $request->query($field));
            }
        }

        $total = (clone $builder)->count();
        $rows = $builder->select(['ae.*', 'u.display_name as actor_name'])
            ->orderByDesc('ae.id')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'event_type' => $row->event_type,
            'resource_type' => $row->resource_type,
            'resource_id' => (int) $row->resource_id,
            'actor' => $row->actor_id === null ? null : [
                'id' => (int) $row->actor_id,
                'display_name' => $row->actor_name,
            ],
            'actor_source' => $row->actor_source,
            'metadata' => $this->decodeJson($row->metadata_json),
            'created_at' => $row->created_at,
        ])->all();

        return ApiResponse::paginated($data, $page, $pageSize, $total);
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
