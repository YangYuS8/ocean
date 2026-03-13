<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(): array
    {
        return [
            'pending_samples' => DB::table('samples')->whereIn('status', ['registered', 'received', 'testing'])->count(),
            'today_inspection_tasks' => DB::table('inspection_tasks')->whereDate('planned_at', now()->toDateString())->count(),
            'open_exceptions' => DB::table('exceptions')->where('status', 'open')->count(),
            'queued_analysis_jobs' => DB::table('analysis_jobs')->whereIn('status', ['queued', 'running'])->count(),
        ];
    }
}
