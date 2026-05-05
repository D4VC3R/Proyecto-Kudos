<?php

namespace App\Services;

use App\Models\User;
use App\Models\Item;
use App\Models\Proposal;
use App\Repositories\KudosRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class KudosService
{
    public function __construct(
        protected KudosRepository $kudosRepository
    ) {}

    /**
     * Orquesta el proceso de recompensa por inicio de sesión consecutivo.
     * Utiliza bloqueo pesimista para evitar condiciones de carrera.
     *
     * @return array{awarded: bool, streak: int, kudos_awarded: int, date: string}
     */
    public function processDailyLogin(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);

            $today = CarbonImmutable::now()->startOfDay();
            $todayYmd = $today->toDateString();

            $previousDate = $lockedUser->last_login_streak_date
                ? CarbonImmutable::parse($lockedUser->last_login_streak_date)->startOfDay()
                : null;

            $newStreak = $this->computeStreak($lockedUser->login_streak_count, $previousDate, $today);

            $lockedUser->login_streak_count = $newStreak;
            if ($newStreak > $lockedUser->max_login_streak_count) {
                $lockedUser->max_login_streak_count = $newStreak;
            }
            $lockedUser->last_login_streak_date = $todayYmd;
            $lockedUser->save();

            $kudosAmount = $this->getDailyLoginReward($newStreak);
            $reason = config('kudos.reasons.daily_login_streak');
            $actionKey = $this->buildActionKey($reason, $lockedUser->id, $todayYmd);

            $awarded = $this->awardIfFirst(
                user: $lockedUser,
                amount: $kudosAmount,
                reason: $reason,
                actionKey: $actionKey,
                referenceType: User::class,
                referenceId: $lockedUser->id,
            );

            return [
                'awarded' => $awarded,
                'streak' => $newStreak,
                'kudos_awarded' => $awarded ? $kudosAmount : 0,
                'date' => $todayYmd,
            ];
        });
    }

    /**
     * Recompensa cuando un usuario vota por primera vez un ítem.
     */
    public function processFirstTimeVote(User $user, string $itemId): bool
    {
        $reason = config('kudos.reasons.vote_first_time_item');
        $amount = config('kudos.rewards.vote_first_time_item');
        $actionKey = $this->buildActionKey($reason, $user->id, $itemId);

        return $this->awardIfFirst($user, $amount, $reason, $actionKey, Item::class, $itemId);
    }

    /**
     * Recompensa cuando la propuesta de un usuario es aceptada por moderación.
     */
    public function processProposalAccepted(User $user, string $proposalId): bool
    {
        $reason = config('kudos.reasons.proposal_accepted');
        $amount = config('kudos.rewards.proposal_accepted');
        $actionKey = $this->buildActionKey($reason, $proposalId);

        return $this->awardIfFirst($user, $amount, $reason, $actionKey, Proposal::class, $proposalId);
    }

    /* -----------------------------------------------------------------
     * MÉTODOS INTERNOS (Abstracción de reglas y utilidades)
     * ----------------------------------------------------------------- */

    private function awardIfFirst(User $user, int $amount, string $reason, string $actionKey, string $referenceType, string $referenceId): bool
    {
        return DB::transaction(function () use ($user, $amount, $reason, $actionKey, $referenceType, $referenceId) {
            $inserted = $this->kudosRepository->insertIfNotExists($user->id, $amount, $reason, $actionKey, $referenceType, $referenceId);

            if (!$inserted) {
                return false;
            }

            $user->increment('total_kudos', $amount);
            return true;
        });
    }

    private function computeStreak(int $currentStreak, ?CarbonImmutable $lastDate, CarbonImmutable $today): int
    {
        if (!$lastDate) return 1;

        $diffDays = $lastDate->diffInDays($today, false);

        if ($diffDays === 0) return max(1, $currentStreak);
        if ($diffDays === 1) return max(1, $currentStreak + 1);

        return 1;
    }

    private function getDailyLoginReward(int $streakCount): int
    {
        $cap = (int) config('kudos.rules.daily_login_streak_cap', 5);
        $boundedStreak = max(1, min($streakCount, $cap));
        $matrix = (array) config('kudos.rewards.daily_login_streak', []);

        return (int) ($matrix[$boundedStreak] ?? 0);
    }

    /**
     * Construye dinámicamente un Action Key uniendo partes con ":"
     */
    private function buildActionKey(string ...$identifiers): string
    {
        return implode(':', $identifiers);
    }
}
