<?php

namespace App\Actions;

use App\Models\Vote;
use App\Services\ItemStatsService;
use Illuminate\Support\Facades\DB;

/**
 * Acción para cambiar la puntuación de un voto existente y actualizar la puntuación media del ítem asociado.
 */
class ChangeVoteAction
{
	public function __construct(protected ItemStatsService $itemStatsService) {}

	/**
	 * Cambia la puntuación de un voto existente y recalcula la media del ítem asociado con AVG sobre los votos reales.
	 *
	 * @param Vote $vote El voto a actualizar
	 * @param array $payload Los datos de la nueva puntuación.
	 * @return Vote Instancia del voto actualizado.
	 */
	public function execute(Vote $vote, array $payload): Vote
	{
		return DB::transaction(function () use ($vote, $payload) {
			// La nota se guarda como float: los medios puntos no se truncan.
			$newScore = isset($payload['score']) ? (float) $payload['score'] : $vote->score;

			$vote->update(['score' => $newScore]);

			$this->itemStatsService->recalculate($vote->item_id);

			return $vote->fresh();
		});
	}
}