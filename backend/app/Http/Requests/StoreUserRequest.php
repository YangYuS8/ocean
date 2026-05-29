<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:64', Rule::unique('users', 'username')],
            'display_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:64'],
        ];
    }
}
