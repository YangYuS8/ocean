<?php

namespace App\Services\Concerns;

trait PaginatesQueries
{
    private function pagination(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($query['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        return [$page, $pageSize, $offset];
    }
}
