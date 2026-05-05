<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KudosRepository
{
    /**
     * Inserta la transacción solo si el action_key no existe.
     */
    public function insertIfNotExists(string $userId, int $amount, string $reason, string $actionKey, string $referenceType, string $referenceId): bool
    {
        $inserted = DB::table('kudos_transactions')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'kudos_amount' => $amount,
            'reason' => $reason,
            'action_key' => $actionKey,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted > 0;
    }
}
