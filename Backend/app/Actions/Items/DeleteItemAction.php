<?php

namespace App\Actions\Items;

use App\Models\Item;
use App\Services\ItemService;

class DeleteItemAction
{
    public function __construct(protected ItemService $itemService)
    {
    }

    public function execute(Item $item): bool
    {
        return $this->itemService->deleteItem($item);
    }
}

