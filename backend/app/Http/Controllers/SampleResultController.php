<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSampleResultRequest;
use App\Services\SampleResultService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SampleResultController extends Controller
{
    public function __construct(private readonly SampleResultService $service)
    {
    }

    public function index(Request $request, int $id)
    {
        $result = $this->service->index($id, $request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function store(StoreSampleResultRequest $request, int $id)
    {
        return ApiResponse::success($this->service->store($id, $request->validated()), 201);
    }
}
