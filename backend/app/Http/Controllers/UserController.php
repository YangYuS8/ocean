<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplaceUserRolesRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserManagementService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $service) {}

    public function index(Request $request)
    {
        $result = $this->service->index($request->query());

        return ApiResponse::paginated($result['data'], $result['page'], $result['pageSize'], $result['total']);
    }

    public function store(StoreUserRequest $request)
    {
        return ApiResponse::success($this->service->store($request->validated()), 201);
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->show($id));
    }

    public function update(int $id, UpdateUserRequest $request)
    {
        return ApiResponse::success($this->service->update($id, $request->validated()));
    }

    public function replaceRoles(int $id, ReplaceUserRolesRequest $request)
    {
        return ApiResponse::success($this->service->replaceRoles($id, $request->validated('roles')));
    }

    public function activate(int $id)
    {
        return ApiResponse::success($this->service->setStatus($id, 'active'));
    }

    public function deactivate(int $id)
    {
        return ApiResponse::success($this->service->setStatus($id, 'inactive'));
    }
}
