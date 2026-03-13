<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class SampleResultService
{
    public function index(int $sampleId, array $query): array
    {
        $this->assertSampleExists($sampleId);

        $builder = DB::table('sample_results as sr')
            ->leftJoin('users as u', 'u.id', '=', 'sr.entered_by')
            ->where('sr.sample_id', $sampleId);

        foreach (['result_type', 'status'] as $field) {
            if (!empty($query[$field])) {
                $builder->where('sr.'.$field, $query[$field]);
            }
        }

        $rows = $builder->select(['sr.*', 'u.display_name as entered_by_name'])
            ->orderByDesc('sr.id')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'sample_id' => (int) $row->sample_id,
            'result_type' => $row->result_type,
            'status' => $row->status,
            'raw_value' => $this->decodeJson($row->raw_value),
            'normalized_value' => $this->decodeJson($row->normalized_value),
            'conclusion' => $row->conclusion,
            'entered_by' => $row->entered_by === null ? null : [
                'id' => (int) $row->entered_by,
                'display_name' => $row->entered_by_name,
            ],
            'entered_at' => $row->entered_at,
        ])->all();
    }

    public function store(int $sampleId, array $payload): array
    {
        $sample = DB::table('samples')->select(['id', 'status'])->where('id', $sampleId)->first();
        if (!$sample) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
        }
        if (in_array($sample->status, ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept results in current state', 409);
        }
        if (!empty($payload['entered_by']) && !DB::table('users')->where('id', (int) $payload['entered_by'])->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        $id = DB::table('sample_results')->insertGetId([
            'sample_id' => $sampleId,
            'result_type' => $payload['result_type'],
            'status' => 'draft',
            'raw_value' => array_key_exists('raw_value', $payload) ? json_encode($payload['raw_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'normalized_value' => array_key_exists('normalized_value', $payload) ? json_encode($payload['normalized_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'conclusion' => $payload['conclusion'] ?? null,
            'entered_by' => $payload['entered_by'] ?? null,
            'entered_at' => now(),
            'notes' => $payload['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (in_array($sample->status, ['registered', 'received'], true)) {
            DB::table('samples')->where('id', $sampleId)->update([
                'status' => 'testing',
                'updated_at' => now(),
            ]);
        }

        return [
            'id' => $id,
            'sample_id' => $sampleId,
            'status' => 'draft',
            'created_at' => DB::table('sample_results')->where('id', $id)->value('created_at'),
        ];
    }

    private function assertSampleExists(int $sampleId): void
    {
        if (!DB::table('samples')->where('id', $sampleId)->exists()) {
            throw new ApiException('NOT_FOUND', 'sample not found', 404);
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
