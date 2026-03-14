<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use App\Services\ModerationAuditLogger;

class UnbanUserAction
{
    public function __construct(protected ModerationAuditLogger $moderationAuditLogger)
    {
    }

    public function execute(User $admin, User $targetUser): User
    {
        $targetUser->is_banned = false;
        $targetUser->banned_at = null;
        $targetUser->banned_until = null;
        $targetUser->ban_reason = null;
        $targetUser->banned_by = null;
        $targetUser->save();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'unban');

        return $targetUser->fresh();
    }
}

