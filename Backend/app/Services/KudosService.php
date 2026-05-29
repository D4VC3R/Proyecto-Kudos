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
     * Recompensa por inicio de sesión diario con sistema de rachas.
     *
     * @param User $user El usuario que inicia sesión.
     * @return array Un array con información sobre la recompensa otorgada, la racha actual, la cantidad de Kudos otorgados, y si se estableció un nuevo récord de racha.
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
     *
     * @param User $user El usuario que realiza el voto.
     * @param string $itemId El ID del ítem por el que se vota.
     * @return int La cantidad de Kudos otorgados por este voto.
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
     *
     * @param User $user El usuario que hizo la propuesta aceptada.
     * @param string $proposalId El ID de la propuesta que fue aceptada.
     * @return bool Retorna true si se otorgaron los Kudos por la propuesta aceptada.
	 */
	public function processProposalAccepted(User $user, string $proposalId): bool
	{
		$reason = config('kudos.reasons.proposal_accepted');
		$amount = config('kudos.rewards.proposal_accepted');
		$actionKey = $this->buildActionKey($reason, $proposalId);

		return $this->awardIfFirst($user, $amount, $reason, $actionKey, Proposal::class, $proposalId);
	}

	/* --------------------
	 * MÉTODOS INTERNOS
	 * -------------------- */

    /**
     * Intenta otorgar Kudos a un usuario por una acción, asegurándose de que solo se otorgue una vez por referencia.
     *
     * @param User $user El usuario que recibirá los Kudos.
     * @param int $amount La cantidad de Kudos a otorgar.
     * @param string $reason La razón o tipo de acción que genera los Kudos.
     * @param string $actionKey Una clave única que identifica esta acción específica para evitar duplicados.
     * @param string $referenceType El tipo de referencia (por ejemplo, clase del modelo relacionado).
     * @param string $referenceId El ID de la referencia (por ejemplo, el ID del modelo relacionado).
     * @return bool Retorna true si se otorgaron los Kudos, false si ya se habían otorgado previamente para esta acción.
     */
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

    /**
     * Calcula la nueva racha de inicio de sesión diario basándose en la fecha del último inicio de sesión y la fecha actual.
     * - Si no hay fecha anterior, se inicia una nueva racha (1).
     * - Si el último inicio de sesión fue hoy, se mantiene la racha actual.
     * - Si el último inicio de sesión fue ayer, se incrementa la racha en 1.
     * - Si el último inicio de sesión fue hace más de un día, se reinicia la racha a 1.
     *
     * @param int $currentStreak La racha actual de inicios de sesión diarios del usuario.
     * @param CarbonImmutable|null $lastDate La fecha del último inicio de sesión registrado para la racha.
     * @param CarbonImmutable $today La fecha actual.
     * @return int La nueva racha de inicio de sesión diario después de evaluarr las fechas.
     */
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
     *
     * @param int $streak La racha actual de inicios de sesión diarios del usuario.
     * @return array Un array que contiene la cantidad de Kudos a otorgar, el valor base, el multiplicador aplicado y si tiene bonificador de puntos.
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
     *
     * @param User $user El usuario que realiza el voto.
     * @return int La cantidad de Kudos a otorgar por este voto.
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

    /**
     * Construye una clave única para identificar una acción específica de Kudos, combinando varios identificadores.
     * Esto se utiliza para asegurar que los Kudos solo se otorguen una vez por acción específica (un inicio de sesión diario, un voto por ítem, etc.).
     * @param string ...$identifiers Una lista de identificadores que describen la acción (tipo de acción, ID de usuario, ID de ítem, fecha, etc.).
     * @return string Una cadena única que representa esta acción específica, utilizada para evitar otorgar Kudos duplicados por la misma acción.
     */
	private function buildActionKey(string ...$identifiers): string
	{
		return implode(':', $identifiers);
	}
}
