<?php

declare(strict_types=1);

use App\Http\ApiKernel;
use App\Http\Request;
use App\Infrastructure\Database;
use App\Service\P0ApiService;
use App\Support\ApiException;
use App\Support\JsonResponse;

require_once dirname(__DIR__) . '/bootstrap.php';

$request = Request::capture();
$path = rtrim($request->path(), '/');

if (!str_starts_with($path === '' ? '/' : $path, '/api')) {
    JsonResponse::success([
        'project' => '海洋样本巡检系统',
        'status' => 'PHP 开发环境已初始化',
    ]);
}

try {
    $kernel = new ApiKernel(new P0ApiService(Database::connection()));
    $result = $kernel->handle($request);

    if ($result['type'] === 'paginated') {
        JsonResponse::paginated(
            $result['payload']['data'],
            $result['payload']['page'],
            $result['payload']['pageSize'],
            $result['payload']['total']
        );
    }

    JsonResponse::success($result['payload'], $result['status'] ?? 200);
} catch (ApiException $exception) {
    JsonResponse::error($exception->errorCode(), $exception->getMessage(), $exception->statusCode());
} catch (Throwable $throwable) {
    error_log($throwable->getMessage());
    JsonResponse::error('INTERNAL_ERROR', 'internal server error', 500);
}
