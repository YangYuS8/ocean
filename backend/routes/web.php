<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'data' => [
            'project' => '海洋样本巡检系统',
            'status' => 'Laravel backend is running',
        ],
    ]);
});
