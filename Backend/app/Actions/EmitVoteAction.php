<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\KudosService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Acción para emitir un voto, manejar 'race conditions' y otorgar kudos por votos.
 */
class EmitVoteAction
{
	public function __construct(protected KudosService $kudosService) {}

	/**
	 * Emite un voto para un ítem, manejando condiciones de carrera y otorgando kudos si es el primer voto del usuario para ese ítem.
	 *
	 * @param User $user El usuario que emite el voto
	 * @param array $voteData Los datos del voto (debe incluir 'item_id' y opcionalmente 'score' y 'type')
	 * @return Vote La instancia del voto emitido o existente
	 * @throws \Throwable
	 */
	public function execute(User $user, array $voteData): Vote
	{
		$existingVote = Vote::where('user_id', $user->id)->where('item_id', $voteData['item_id'])->first();
		if ($existingVote) {
			$existingVote->setAttribute('was_existing', true);
			$existingVote->setAttribute('kudos_awarded', 0);
			return $existingVote;
		}

		return DB::transaction(function () use ($user, $voteData) {
			$voteData['user_id'] = $user->id;
			$voteData['type'] = $voteData['type'] ?? Vote::TYPE_VOTE;

			try {
				$vote = Vote::create($voteData);
			} catch (QueryException $e) {
				// Manejo de condición de carrera
				$raceVote = Vote::where('user_id', $user->id)->where('item_id', $voteData['item_id'])->first();
				if ($raceVote) {
					$raceVote->setAttribute('was_existing', true);
					$raceVote->setAttribute('kudos_awarded', 0);
					return $raceVote;
				}
				throw $e;
			}

			$kudosAwarded = 0;

			if ($voteData['type'] === Vote::TYPE_VOTE) {
				$kudosAwarded = $this->kudosService->processFirstTimeVote($user, $voteData['item_id']);

				$this->updateItemAverages($voteData['item_id'], (int) $voteData['score']);
			}

			$vote->setAttribute('was_existing', false);
			$vote->setAttribute('kudos_awarded', $kudosAwarded);

			return $vote;
		});
	}
	/**
	 * Actualiza el conteo de votos y la puntuación media.
	 *
	 * @param string $itemId El ID del ítem a actualizar
	 * @param int $newScore La nueva puntuación a incluir en el cálculo del promedio
	 */

	private function updateItemAverages(string $itemId, int $newScore): void
	{
		$item = Item::lockForUpdate()->findOrFail($itemId);
		$newCount = $item->vote_count + 1;
		$newAvg = (($item->vote_avg * $item->vote_count) + $newScore) / $newCount;

		$item->update([
			'vote_count' => $newCount,
			'vote_avg' => round($newAvg, 2),
		]);
	}
}