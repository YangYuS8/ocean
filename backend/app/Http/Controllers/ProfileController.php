<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\ProfileService;
use App\Support\ApiResponse;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    public function showProfile()
    {
        return ApiResponse::success($this->service->showProfile());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        return ApiResponse::success($this->service->updateProfile($request->validated()));
    }

    public function showSettings()
    {
        return ApiResponse::success($this->service->showSettings());
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        return ApiResponse::success($this->service->updateSettings($request->validated()));
    }
}
