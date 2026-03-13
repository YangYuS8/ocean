<?php

namespace App\Http\Requests;

class StoreAnalysisJobRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'sample_id' => ['required', 'integer'],
            'job_type' => ['required', 'string'],
            'params' => ['nullable', 'array'],
            'queued_by' => ['nullable', 'integer'],
        ];
    }
}
