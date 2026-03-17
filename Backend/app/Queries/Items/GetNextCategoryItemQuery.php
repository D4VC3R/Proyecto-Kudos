<?php

namespace App\Queries\Items;

use App\Models\Category;
use App\Models\User;
use App\Services\NextCategoryItemService;

class GetNextCategoryItemQuery
{
    public function __construct(protected NextCategoryItemService $nextCategoryItemService)
    {
    }

    /**
     * @return array{item: \App\Models\Item, remaining: int}|null
     */
    public function execute(User $user, Category $category): ?array
    {
        return $this->nextCategoryItemService->getNextItem($user, $category);
    }
}

