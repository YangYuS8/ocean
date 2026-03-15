<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSampleRequest;
use App\Http\Requests\StoreSampleMainImageRequest;
use App\Services\SampleService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function __construct(private readonly SampleService $service)
    {
    }

    public function index(Request $request)
    {
        $result = $this->service->index($request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function store(StoreSampleRequest $request)
    {
        return ApiResponse::success($this->service->store($request->validated()), 201);
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->show($id));
    }

    public function storeMainImage(int $id, StoreSampleMainImageRequest $request)
    {
        return ApiResponse::success($this->service->storeMainImage($id, $request->file('image')), 201);
    }

    public function showMainImageContent(int $id)
    {
        return $this->service->showMainImageContent($id);
    }

    public function showImageSuggestion(int $id)
    {
        return ApiResponse::success($this->service->showImageSuggestion($id));
    }
}
