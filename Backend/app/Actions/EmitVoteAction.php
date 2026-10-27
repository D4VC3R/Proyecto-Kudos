<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Vote;
use App\Services\ItemStatsService;
use App\Services\KudosService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Acción para emitir un voto, manejar 'race conditions' y otorgar kudos por votos.
 */
class EmitVoteAction
{
	public function __construct(protected KudosService $kudosService, protected ItemStatsService $itemStatsService) {}

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

				$this->itemStatsService->recalculate($voteData['item_id']);
			}

			$vote->setAttribute('was_existing', false);
			$vote->setAttribute('kudos_awarded', $kudosAwarded);

			return $vote;
		});
	}
}