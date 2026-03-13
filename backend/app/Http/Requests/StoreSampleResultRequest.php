<?php

namespace App\Http\Requests;

class StoreSampleResultRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'result_type' => ['required', 'string'],
            'raw_value' => ['nullable', 'array'],
            'normalized_value' => ['nullable', 'array'],
            'conclusion' => ['nullable', 'string'],
            'entered_by' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
