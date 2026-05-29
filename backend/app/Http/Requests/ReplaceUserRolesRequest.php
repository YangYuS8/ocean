<?php

namespace App\Http\Requests;

class ReplaceUserRolesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:64'],
        ];
    }
}
