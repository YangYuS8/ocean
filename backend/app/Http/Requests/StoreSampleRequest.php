<?php

namespace App\Http\Requests;

class StoreSampleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sample_code' => ['required', 'string'],
            'inspection_task_id' => ['nullable', 'integer'],
            'sample_type' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'collection_time' => ['nullable', 'date'],
            'location_text' => ['nullable', 'string'],
            'collector_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
