<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KudosService
{
    public function awardIfFirst(
        User $user,
        int $kudosAmount,
        string $reason,
        string $actionKey,
        string $referenceType,
        string $referenceId,
    ): bool {
        return DB::transaction(function () use ($user, $kudosAmount, $reason, $actionKey, $referenceType, $referenceId) {
            $inserted = DB::table('kudos_transactions')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'kudos_amount' => $kudosAmount,
                'reason' => $reason,
                'action_key' => $actionKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return false;
            }

            $user->increment('total_kudos', $kudosAmount);

            return true;
        });
    }
}

