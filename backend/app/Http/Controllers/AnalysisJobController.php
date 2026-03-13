<?php

namespace App\Http\Controllers;

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
}
