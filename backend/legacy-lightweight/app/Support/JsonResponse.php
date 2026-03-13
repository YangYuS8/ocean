<?php

declare(strict_types=1);

namespace App\Support;

final class JsonResponse
{
    public static function send(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data, int $statusCode = 200): never
    {
        self::send(['data' => $data], $statusCode);
    }

    public static function paginated(array $data, int $page, int $pageSize, int $total): never
    {
        self::send([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
            ],
        ]);
    }

    public static function error(string $code, string $message, int $statusCode): never
    {
        self::send([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }
}
