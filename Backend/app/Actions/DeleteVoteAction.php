<?php

namespace App\Actions;

use App\Models\Vote;
use App\Services\ItemStatsService;
use Illuminate\Support\Facades\DB;

/**
 * Acción para eliminar un voto y actualizar la puntuación media del ítem asociado.
 */
class DeleteVoteAction
{
	public function __construct(protected ItemStatsService $itemStatsService) {}

	/**
	 * Elimina un voto y recalcula la media del ítem asociado con AVG sobre los votos reales.
	 *
	 * @param Vote $vote El voto a eliminar
	 * @return bool Resultado de la transacción
	 */
	public function execute(Vote $vote): bool
	{
		return DB::transaction(function () use ($vote) {
			if ($vote->type === Vote::TYPE_SKIP) {
				return (bool) $vote->delete();
			}

			$deleted = (bool) $vote->delete();

			// Se recalcula después de borrar: AVG ya no incluye el voto eliminado.
			$this->itemStatsService->recalculate($vote->item_id);

			return $deleted;
		});
	}
}