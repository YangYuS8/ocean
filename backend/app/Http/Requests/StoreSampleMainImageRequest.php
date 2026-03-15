<?php

namespace App\Http\Requests;

class StoreSampleMainImageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp'],
        ];
    }
}
