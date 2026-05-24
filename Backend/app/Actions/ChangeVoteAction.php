<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class ChangeVoteAction
{
	/**
	 * Cambia la puntuación de un voto existente y actualiza la puntuación media del ítem asociado.
	 *
	 * @param Vote $vote El voto a actualizar
	 * @param array $payload Los datos de la nueva puntuación.
	 * @return Vote Instancia del voto actualizado.
	 */
	
	public function execute(Vote $vote, array $payload): Vote
	{
		return DB::transaction(function () use ($vote, $payload) {
			$item = Item::lockForUpdate()->findOrFail($vote->item_id);

			$newScore = (int) ($payload['score'] ?? $vote->score);
			$oldScore = (int) $vote->score;

			$vote->update(['score' => $newScore]);

			$newAvg = (($item->vote_avg * $item->vote_count) - $oldScore + $newScore) / $item->vote_count;
			$item->update(['vote_avg' => round($newAvg, 2)]);

			return $vote->fresh();
		});
	}
}