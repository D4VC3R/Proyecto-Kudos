<?php

namespace App\Queries\Items;

use App\Models\User;
use App\Services\ItemService;
use Illuminate\Support\Collection;

class ListMyItemsQuery
{
    public function __construct(protected ItemService $itemService)
    {
    }

    public function execute(User $user): Collection
    {
        $items = $this->itemService->getItemsByUser($user);
        $this->itemService->enrichItemsWithUserContext($items, $user);

        return $items;
    }
}

