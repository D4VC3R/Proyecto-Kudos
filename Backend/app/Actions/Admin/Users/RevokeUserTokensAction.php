<?php

namespace App\Actions\Admin\Users;

use App\Models\User;
use App\Services\ModerationAuditLogger;

class RevokeUserTokensAction
{
    public function __construct(protected ModerationAuditLogger $moderationAuditLogger)
    {
    }

    public function execute(User $admin, User $targetUser): int
    {
        $revoked = $targetUser->tokens()->count();
        $targetUser->tokens()->delete();

        $this->moderationAuditLogger->logUserBanChange($targetUser, $admin, 'revoke_tokens', [
            'revoked_tokens' => $revoked,
        ]);

        return $revoked;
    }
}

