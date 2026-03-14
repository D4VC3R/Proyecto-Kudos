<?php

namespace App\Actions\Admin\Items;

use App\Models\Item;
use App\Models\User;
use App\Services\ItemService;
use App\Services\ModerationAuditLogger;

class ModerateItemStatusAction
{
    public function __construct(
        protected ItemService $itemService,
        protected ModerationAuditLogger $moderationAuditLogger,
    ) {
    }

    public function execute(User $admin, Item $item, string $newStatus, ?string $reason = null): Item
    {
        $previousStatus = $item->status;
        $updated = $this->itemService->updateItem($item, ['status' => $newStatus]);

        $this->moderationAuditLogger->logItemModeration(
            $updated,
            $admin,
            'admin_moderate_item_status',
            ['from' => $previousStatus, 'to' => $newStatus],
            $reason,
        );

        return $updated;
    }
}

