<?php

namespace App\Http\Requests;

class SucceedAnalysisJobRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'result_summary' => ['nullable', 'string'],
            'suggestion' => ['nullable', 'array'],
        ];
    }
}
