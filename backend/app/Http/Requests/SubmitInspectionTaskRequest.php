<?php

namespace App\Http\Requests;

class SubmitInspectionTaskRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'operator_id' => ['nullable', 'integer'],
            'submission_note' => ['nullable', 'string'],
        ];
    }
}
