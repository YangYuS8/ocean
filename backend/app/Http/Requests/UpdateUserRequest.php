<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $userId = (int) $this->route('id');

        return [
            'username' => ['sometimes', 'string', 'max:64', Rule::unique('users', 'username')->ignore($userId)],
            'display_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($userId)],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'password' => ['sometimes', 'string', 'min:8'],
        ];
    }
}
