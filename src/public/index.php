<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'project' => '海洋样本巡检系统',
    'status' => 'PHP 开发环境已初始化',
], JSON_UNESCAPED_UNICODE);
