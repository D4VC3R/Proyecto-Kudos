<?php

namespace App\Services;

final class KudosRules
{
    private const KEY_VOTE_FIRST_TIME_ITEM = 'vote_first_time_item';
    private const KEY_PROPOSAL_ACCEPTED = 'proposal_accepted';
    private const KEY_DAILY_LOGIN_STREAK = 'daily_login_streak';

    public static function rewardForVoteFirstTimeItem(): int
    {
        return self::reward(self::KEY_VOTE_FIRST_TIME_ITEM);
    }

    public static function rewardForAcceptedProposal(): int
    {
        return self::reward(self::KEY_PROPOSAL_ACCEPTED);
    }

    public static function reasonForVoteFirstTimeItem(): string
    {
        return self::reason(self::KEY_VOTE_FIRST_TIME_ITEM);
    }

    public static function reasonForAcceptedProposal(): string
    {
        return self::reason(self::KEY_PROPOSAL_ACCEPTED);
    }

    public static function reasonForDailyLoginStreak(): string
    {
        return self::reason(self::KEY_DAILY_LOGIN_STREAK);
    }

    public static function actionKey(string $reason, string $userId, string $referenceType, string $referenceId): string
    {
        $shortReferenceType = class_basename($referenceType);

        return $reason . ':' . $userId . ':' . $shortReferenceType . ':' . $referenceId;
    }

    public static function actionKeyForVoteFirstTimeItem(string $userId, string $itemId): string
    {
        return self::reasonForVoteFirstTimeItem() . ':' . $userId . ':' . $itemId;
    }

    public static function actionKeyForAcceptedProposal(string $proposalId): string
    {
        return self::reasonForAcceptedProposal() . ':' . $proposalId;
    }

    public static function actionKeyForDailyLogin(string $userId, string $dateYmd): string
    {
        return self::reasonForDailyLoginStreak() . ':' . $userId . ':' . $dateYmd;
    }

    public static function rewardForDailyLoginStreak(int $streakCount): int
    {
        $cap = (int) config('kudos.rules.daily_login_streak_cap', 5);
        $boundedStreak = max(1, min($streakCount, $cap));
        $matrix = (array) config('kudos.rewards.daily_login_streak', []);

        return (int) ($matrix[$boundedStreak] ?? 0);
    }

    private static function reward(string $key): int
    {
        return (int) config("kudos.rewards.$key");
    }

    private static function reason(string $key): string
    {
        return (string) config("kudos.reasons.$key");
    }
}

