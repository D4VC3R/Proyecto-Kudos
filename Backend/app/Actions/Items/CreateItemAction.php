<?php

namespace App\Actions\Items;

use App\Models\Item;
use App\Models\User;
use App\Services\ItemService;

class CreateItemAction
{
    public function __construct(protected ItemService $itemService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(array $payload, User $user): Item
    {
        return $this->itemService->createItem($payload, $user);
    }
}

