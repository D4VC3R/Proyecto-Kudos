<?php

namespace App\Queries\Items;

use App\Services\ItemService;
use Illuminate\Pagination\LengthAwarePaginator;

class ListActiveItemsQuery
{
    public function __construct(protected ItemService $itemService)
    {
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->itemService->getActiveItems($filters, $perPage);
    }
}

