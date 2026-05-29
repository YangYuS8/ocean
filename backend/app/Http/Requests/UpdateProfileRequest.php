<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $actor = $this->attributes->get('ocean_actor');
        $userId = is_array($actor) && isset($actor['id']) ? (int) $actor['id'] : 0;

        return [
            'display_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }
}
