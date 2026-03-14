<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ModerationAuditLogger
{
    public function logProposalReview(Proposal $proposal, User $admin, string $status, ?string $notes = null): void
    {
        Log::info('admin.proposal.review', [
            'proposal_id' => $proposal->id,
            'admin_id' => $admin->id,
            'status' => $status,
            'admin_notes' => $notes,
            'reviewed_at' => now()->toIso8601String(),
        ]);
    }

    public function logItemModeration(Item $item, User $admin, string $action, array $changes = [], ?string $reason = null): void
    {
        Log::info('admin.item.moderation', [
            'item_id' => $item->id,
            'admin_id' => $admin->id,
            'action' => $action,
            'changes' => $changes,
            'reason' => $reason,
            'moderated_at' => now()->toIso8601String(),
        ]);
    }

    public function logUserBanChange(User $targetUser, User $admin, string $action, array $context = []): void
    {
        Log::info('admin.user.ban', [
            'user_id' => $targetUser->id,
            'admin_id' => $admin->id,
            'action' => $action,
            'context' => $context,
            'changed_at' => now()->toIso8601String(),
        ]);
    }
}

