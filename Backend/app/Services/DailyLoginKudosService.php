<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DailyLoginKudosService
{
    public function __construct(protected KudosService $kudosService)
    {
    }

    /**
     * @return array{awarded: bool, streak: int, kudos_awarded: int, date: string}
     */
    public function handleSuccessfulLogin(User $user): array
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
            $lockedUser->last_login_streak_date = $todayYmd;
            $lockedUser->save();

            $kudosAmount = KudosRules::rewardForDailyLoginStreak($newStreak);

            $awarded = $this->kudosService->awardIfFirst(
                user: $lockedUser,
                kudosAmount: $kudosAmount,
                reason: KudosRules::reasonForDailyLoginStreak(),
                actionKey: KudosRules::actionKeyForDailyLogin($lockedUser->id, $todayYmd),
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

    private function computeStreak(int $currentStreak, ?CarbonImmutable $lastDate, CarbonImmutable $today): int
    {
        if (!$lastDate) {
            return 1;
        }

        $diffDays = $lastDate->diffInDays($today, false);

        if ($diffDays === 0) {
            return max(1, $currentStreak);
        }

        if ($diffDays === 1) {
            return max(1, $currentStreak + 1);
        }

        return 1;
    }
}

