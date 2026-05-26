<?php

namespace App\Http\Requests;

class StartInspectionTaskRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'operator_id' => ['nullable', 'integer'],
        ];
    }
}
