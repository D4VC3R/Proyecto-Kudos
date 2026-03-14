<?php

namespace App\Services;

use App\Models\KudosTransaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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
            try {
                KudosTransaction::create([
                    'user_id' => $user->id,
                    'kudos_amount' => $kudosAmount,
                    'reason' => $reason,
                    'action_key' => $actionKey,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);
            } catch (QueryException $e) {
                if ($this->isDuplicateActionKeyException($e)) {
                    return false;
                }

                throw $e;
            }

            $user->increment('total_kudos', $kudosAmount);

            return true;
        });
    }

    private function isDuplicateActionKeyException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        // PostgreSQL duplicate key: 23505, MySQL duplicate key family: 23000.
        return in_array($sqlState, ['23505', '23000'], true);
    }
}

