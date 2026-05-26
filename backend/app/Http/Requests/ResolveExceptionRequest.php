<?php

namespace App\Http\Requests;

class ResolveExceptionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'resolved_by' => ['nullable', 'integer'],
            'resolve_note' => ['nullable', 'string'],
        ];
    }
}
