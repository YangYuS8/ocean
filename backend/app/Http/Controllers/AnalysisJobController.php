<?php

namespace App\Http\Controllers;

use App\Http\Requests\FailAnalysisJobRequest;
use App\Http\Requests\SucceedAnalysisJobRequest;
use App\Http\Requests\StoreAnalysisJobRequest;
use App\Services\AnalysisJobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AnalysisJobController extends Controller
{
    public function __construct(private readonly AnalysisJobService $service)
    {
    }

    public function index(Request $request)
    {
        $result = $this->service->index($request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function store(StoreAnalysisJobRequest $request)
    {
        return ApiResponse::success($this->service->store($request->validated()), 201);
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->show($id));
    }

    public function start(int $id)
    {
        return ApiResponse::success($this->service->start($id));
    }

    public function succeed(int $id, SucceedAnalysisJobRequest $request)
    {
        return ApiResponse::success($this->service->succeed($id, $request->validated()));
    }

    public function fail(int $id, FailAnalysisJobRequest $request)
    {
        return ApiResponse::success($this->service->fail($id, $request->validated()));
    }

    public function cancel(int $id)
    {
        return ApiResponse::success($this->service->cancel($id));
    }

    public function retry(int $id)
    {
        return ApiResponse::success($this->service->retry($id), 201);
    }
}
