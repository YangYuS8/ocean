<?php

namespace App\Http\Requests;

class FailAnalysisJobRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'error_message' => ['nullable', 'string'],
        ];
    }
}
