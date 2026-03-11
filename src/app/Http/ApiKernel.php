<?php

declare(strict_types=1);

namespace App\Http;

use App\Service\P0ApiService;
use App\Support\ApiException;

final class ApiKernel
{
    public function __construct(private readonly P0ApiService $service)
    {
    }

    public function handle(Request $request): array
    {
        $method = $request->method();
        $path = rtrim($request->path(), '/');
        if ($path === '') {
            $path = '/';
        }

        return match (true) {
            $method === 'GET' && $path === '/api/dashboard/summary' => ['type' => 'success', 'payload' => $this->service->dashboardSummary()],
            $method === 'GET' && $path === '/api/inspection-tasks' => ['type' => 'paginated', 'payload' => $this->service->listInspectionTasks($request->query())],
            $method === 'GET' && preg_match('#^/api/inspection-tasks/(\d+)$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->getInspectionTask((int) $matches[1])],
            $method === 'POST' && preg_match('#^/api/inspection-tasks/(\d+)/start$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->startInspectionTask((int) $matches[1], $request->body())],
            $method === 'POST' && preg_match('#^/api/inspection-tasks/(\d+)/submit$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->submitInspectionTask((int) $matches[1], $request->body())],
            $method === 'GET' && $path === '/api/samples' => ['type' => 'paginated', 'payload' => $this->service->listSamples($request->query())],
            $method === 'POST' && $path === '/api/samples' => ['type' => 'success', 'status' => 201, 'payload' => $this->service->createSample($request->body())],
            $method === 'GET' && preg_match('#^/api/samples/(\d+)$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->getSample((int) $matches[1])],
            $method === 'GET' && preg_match('#^/api/samples/(\d+)/results$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->listSampleResults((int) $matches[1], $request->query())],
            $method === 'POST' && preg_match('#^/api/samples/(\d+)/results$#', $path, $matches) === 1 => ['type' => 'success', 'status' => 201, 'payload' => $this->service->createSampleResult((int) $matches[1], $request->body())],
            $method === 'GET' && $path === '/api/exceptions' => ['type' => 'paginated', 'payload' => $this->service->listExceptions($request->query())],
            $method === 'POST' && $path === '/api/exceptions' => ['type' => 'success', 'status' => 201, 'payload' => $this->service->createException($request->body())],
            $method === 'POST' && preg_match('#^/api/exceptions/(\d+)/resolve$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->resolveException((int) $matches[1], $request->body())],
            $method === 'GET' && $path === '/api/analysis-jobs' => ['type' => 'paginated', 'payload' => $this->service->listAnalysisJobs($request->query())],
            $method === 'POST' && $path === '/api/analysis-jobs' => ['type' => 'success', 'status' => 201, 'payload' => $this->service->createAnalysisJob($request->body())],
            $method === 'GET' && preg_match('#^/api/analysis-jobs/(\d+)$#', $path, $matches) === 1 => ['type' => 'success', 'payload' => $this->service->getAnalysisJob((int) $matches[1])],
            default => throw new ApiException('NOT_FOUND', 'resource not found', 404),
        };
    }
}
