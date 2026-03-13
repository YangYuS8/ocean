<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveExceptionRequest;
use App\Http\Requests\StoreExceptionRequest;
use App\Services\ExceptionService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function __construct(private readonly ExceptionService $service)
    {
    }

    public function index(Request $request)
    {
        $result = $this->service->index($request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function store(StoreExceptionRequest $request)
    {
        return ApiResponse::success($this->service->store($request->validated()), 201);
    }

    public function resolve(ResolveExceptionRequest $request, int $id)
    {
        return ApiResponse::success($this->service->resolve($id, $request->validated()));
    }
}
