<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();
        $attribute = array_key_first($failed);
        $rule = $attribute !== null ? array_key_first($failed[$attribute]) : null;
        $message = $validator->errors()->first();

        if ($attribute !== null && $rule === 'Required') {
            $message = sprintf('%s is required', $attribute);
        }

        throw new HttpResponseException(response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
            ],
        ], 422));
    }
}
