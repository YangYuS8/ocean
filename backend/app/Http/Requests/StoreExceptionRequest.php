<?php

namespace App\Http\Requests;

class StoreExceptionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string'],
            'resource_id' => ['required', 'integer'],
            'category' => ['required', 'string'],
            'severity' => ['nullable', 'string'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'reported_by' => ['nullable', 'integer'],
        ];
    }
}
