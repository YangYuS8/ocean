<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\ApiResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function summary()
    {
        return ApiResponse::success($this->dashboardService->summary());
    }
}
