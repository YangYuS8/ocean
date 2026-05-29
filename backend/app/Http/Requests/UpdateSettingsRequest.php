<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'nullable', 'string', 'max:20'],
            'display_density' => ['sometimes', 'string', Rule::in(['comfortable', 'compact'])],
            'default_workspace_tab' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings_json' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
