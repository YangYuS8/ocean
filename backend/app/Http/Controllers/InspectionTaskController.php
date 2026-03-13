<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartInspectionTaskRequest;
use App\Http\Requests\SubmitInspectionTaskRequest;
use App\Services\InspectionTaskService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class InspectionTaskController extends Controller
{
    public function __construct(private readonly InspectionTaskService $service)
    {
    }

    public function index(Request $request)
    {
        $result = $this->service->index($request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->show($id));
    }

    public function start(StartInspectionTaskRequest $request, int $id)
    {
        return ApiResponse::success($this->service->start($id, $request->validated()));
    }

    public function submit(SubmitInspectionTaskRequest $request, int $id)
    {
        return ApiResponse::success($this->service->submit($id, $request->validated()));
    }
}
