<?php

namespace App\Services;

use App\Models\User;
use App\Models\Item;
use App\Models\Proposal;
use App\Models\KudosTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KudosService
{
	/**
	 * Orquesta el proceso de recompensa por inicio de sesión consecutivo.
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

			$isNewRecord = false;
			if ($newStreak > $lockedUser->max_login_streak_count) {
				$lockedUser->max_login_streak_count = $newStreak;
				$isNewRecord = true;
			}

			$lockedUser->last_login_streak_date = $todayYmd;
			$lockedUser->save();

			$rewardData = $this->getDailyLoginReward($newStreak);
			$kudosAmount = $rewardData['amount'];

			$reason = config('kudos.reasons.daily_login_streak');
			$actionKey = $this->buildActionKey($reason, $lockedUser->id, $todayYmd);

			$awarded = $this->awardIfFirst(
				user: $lockedUser,
				amount: $kudosAmount,
				reason: $reason,
				actionKey: $actionKey,
				referenceType: User::class,
				referenceId: $lockedUser->id
			);

			return [
				'awarded'       => $awarded,
				'streak'        => $newStreak,
				'kudos_awarded' => $awarded ? $kudosAmount : 0,
				'base_kudos'    => $rewardData['base'],
				'multiplier'    => $rewardData['multiplier'],
				'server_date'   => $todayYmd,
				'is_new_record' => $isNewRecord,
				'is_critical'   => $rewardData['is_critical'],
			];
		});
	}

	/**
	 * Recompensa cuando un usuario vota por primera vez un ítem.
	 * Incorpora Anti-Farming: Rendimientos decrecientes y Hard Cap.
	 */
	public function processFirstTimeVote(User $user, string $itemId): int
	{
		$amount = $this->calculateVoteReward($user);

		if ($amount <= 0) {
			return 0; // Límite alcanzado
		}
		$reason = config('kudos.reasons.vote_first_time_item');
		$actionKey = $this->buildActionKey($reason, $user->id, $itemId);

		$awarded = $this->awardIfFirst($user, $amount, $reason, $actionKey, Item::class, $itemId);

		return $awarded ? $amount : 0;
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
			$inserted = KudosTransaction::insertIfNotExists(
				$user->id, $amount, $reason, $actionKey, $referenceType, $referenceId
			);

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
		$diffDays = (int) $lastDate->diffInDays($today, false);
		if ($diffDays === 0) return max(1, $currentStreak);
		if ($diffDays === 1) return max(1, $currentStreak + 1);
		return 1;
	}

	/**
	 * Calcula la recompensa de Kudos por inicio de sesión diario basándose en la racha actual.
	 * Incorpora un sistema de bonificación aleatoria (Critical Hit) para hacer la experiencia más emocionante.
	 */
	private function getDailyLoginReward(int $streak): array
	{
		$cap = config('kudos.rules.daily_login_streak_cap', 5);
		$effectiveStreak = min($streak, $cap);
		$config = config("kudos.rewards.daily_login_streak.{$effectiveStreak}");
		$base = $config['base'];
		$reward = $base;
		$multiplier = 1;
		$isCritical = false;

		if (random_int(1, 100) <= $config['bonus_chance']) {
			$multiplier = $config['bonus_multiplier'];
			$reward = $base * $multiplier;
			$isCritical = true;
		}

		return [
			'amount' => $reward,
			'base' => $base,
			'multiplier' => $multiplier,
			'is_critical' => $isCritical
		];
	}

	/**
	 * Calcula la cantidad de Kudos por voto basándose en el historial de actividad diaria (Redis).
	 */
	private function calculateVoteReward(User $user): int
	{
		$cacheKey = "user:{$user->id}:votes_today";

		if (Cache::add($cacheKey, 1, now()->endOfDay())) {
			$votesToday = 1;
		} else {
			$votesToday = Cache::increment($cacheKey);
		}

		$config = config('kudos.voting');

		if ($votesToday > $config['daily_cap']) {
			return 0; // Limite
		}

		if ($votesToday > $config['diminishing_returns']['threshold']) {
			return $config['diminishing_returns']['new_reward'];
		}

		return $config['reward'];
	}

	private function buildActionKey(string ...$identifiers): string
	{
		return implode(':', $identifiers);
	}
}