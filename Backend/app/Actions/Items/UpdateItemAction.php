<?php

namespace App\Actions\Items;

use App\Models\Item;
use App\Services\ItemService;

class UpdateItemAction
{
    public function __construct(protected ItemService $itemService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(Item $item, array $payload): Item
    {
        return $this->itemService->updateItem($item, $payload);
    }
}

