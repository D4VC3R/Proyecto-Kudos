<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use App\Services\ModerationAuditLogger;

class BanUserAction
{
    public function __construct(protected ModerationAuditLogger $moderationAuditLogger)
    {
    }

    public function execute(User $admin, User $targetUser, bool $isPermanent, ?int $days, string $reason): User
    {
        $targetUser->is_banned = true;
        $targetUser->banned_at = now();
        $targetUser->banned_until = $isPermanent ? null : now()->addDays((int) $days);
        $targetUser->ban_reason = $reason;
        $targetUser->banned_by = $admin->id;
        $targetUser->save();

        $targetUser->tokens()->delete();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'ban', [
            'is_permanent' => $isPermanent,
            'days' => $isPermanent ? null : $days,
            'reason' => $reason,
        ]);

        return $targetUser->fresh();
    }
}

