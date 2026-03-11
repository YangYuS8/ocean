<?php

declare(strict_types=1);

namespace App\Support;

final class Validator
{
    public static function requireFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new ApiException('VALIDATION_ERROR', sprintf('%s is required', $field), 422);
            }
        }
    }
}
