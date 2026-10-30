<?php

namespace App\Actions;

use App\Models\Vote;
use App\Services\ItemStatsService;
use App\Services\KudosService;
use Illuminate\Support\Facades\DB;

/**
 * Acción para actualizar un voto existente: cambiar la nota de una valoración o cambiar su tipo (F2-02).
 *
 * Transiciones permitidas:
 * - want / not_interested / skip (heredado) -> vote: guarda la nota, recalcula la media del ítem y otorga los kudos
 *   de primera valoración (idempotentes por usuario e ítem).
 * - want <-> not_interested (y skip -> cualquiera de los dos): sin nota, sin recalcular y sin kudos.
 * - vote -> vote: cambia la nota y recalcula, sin kudos.
 * vote -> want / not_interested / skip lo rechaza UpdateVoteRequest (hay que borrar el voto y crearlo de nuevo).
 */
class ChangeVoteAction
{
	public function __construct(protected ItemStatsService $itemStatsService, protected KudosService $kudosService) {}

	/**
	 * Aplica el cambio de nota y/o de tipo al voto y recalcula la media del ítem asociado con AVG sobre los votos reales.
	 *
	 * @param Vote $vote El voto a actualizar
	 * @param array $payload Los datos validados: type (tipo destino, opcional) y score.
	 * @return Vote Instancia del voto actualizado, con el atributo kudos_awarded (kudos otorgados en este cambio).
	 */
	public function execute(Vote $vote, array $payload): Vote
	{
		return DB::transaction(function () use ($vote, $payload) {
			$previousType = $vote->type;
			$newType = $payload['type'] ?? $previousType;
			$kudosAwarded = 0;

			if (!in_array($newType, Vote::RATED_TYPES, true)) {
				// want <-> not_interested (y skip heredado): sin nota, sin medias y sin kudos (F2-02)
				$vote->update(['type' => $newType, 'score' => null]);
			} else {
				// La nota se guarda como float: los medios puntos no se truncan.
				$newScore = isset($payload['score']) ? (float) $payload['score'] : $vote->score;

				$vote->update(['type' => $newType, 'score' => $newScore]);

				// Primera valoración desde want/not_interested: kudos idempotentes por (usuario, ítem), como al emitir.
				// vote -> vote no llama a KudosService: incrementaría el contador diario sin dar nada.
				if (!in_array($previousType, Vote::RATED_TYPES, true)) {
					$kudosAwarded = $this->kudosService->processFirstTimeVote($vote->user, $vote->item_id);
				}

				$this->itemStatsService->recalculate($vote->item_id);
			}

			$updatedVote = $vote->fresh();
			$updatedVote->setAttribute('kudos_awarded', $kudosAwarded);

			return $updatedVote;
		});
	}
}
