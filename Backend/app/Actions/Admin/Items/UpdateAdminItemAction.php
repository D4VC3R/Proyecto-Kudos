<?php

namespace App\Actions\Admin\Items;

use App\Models\Item;
use App\Models\User;
use App\Services\ItemService;
use App\Services\ModerationAuditLogger;

class UpdateAdminItemAction
{
    public function __construct(
        protected ItemService $itemService,
        protected ModerationAuditLogger $moderationAuditLogger,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(User $admin, Item $item, array $payload, ?string $reason = null): Item
    {
        $before = $item->only(['name', 'description', 'images', 'status', 'category_id']);
        $updated = $this->itemService->updateItem($item, $payload);
        $after = $updated->only(['name', 'description', 'images', 'status', 'category_id']);

        $this->moderationAuditLogger->logItemModeration(
            $updated,
            $admin,
            'admin_update_item',
            [
                'before' => $before,
                'after' => $after,
            ],
            $reason,
        );

        return $updated;
    }
}

