<?php

namespace App\Services;

use App\Models\User;
use App\Models\Item;
use App\Models\Vote;
use App\Repositories\VoteRepository;
use Illuminate\Support\Facades\DB;

class VoteService
{
	// Definimos cuántos Kudos da votar como una constante para tenerlo centralizado
	public const KUDOS_FOR_VOTING = 5;

	public function __construct(protected VoteRepository $voteRepository)
	{}

	/**
	 * Lógica principal para emitir un voto y dar recompensas.
	 */
	public function emitVote(User $user, array $voteData): Vote
	{
		// 1. Comprobar si ya ha votado a este ítem.
		$existingVote = $this->voteRepository->findByUserAndItem($user->id, $voteData['item_id']);
		if ($existingVote) {
			abort(400, 'Ya has emitido un voto para este elemento. Si quieres cambiarlo, edita tu voto actual.');
		}

		// 2. INICIO DE LA TRANSACCIÓN
		return DB::transaction(function () use ($user, $voteData) {

			// A. Guardar el voto (Añadimos el user_id al array validado)
			$voteData['user_id'] = $user->id;
			$vote = $this->voteRepository->create($voteData);

			// B. Registrar la transacción de Kudos (Relación polimórfica MorphMany)
			$vote->kudosTransactions()->create([
				'user_id' => $user->id,
				'kudos_amount' => self::KUDOS_FOR_VOTING,
				'reason' => 'item_voted'
			]);

			// C. Actualizar los puntos totales del usuario
			$user->increment('total_kudos', self::KUDOS_FOR_VOTING);

			// D. Actualizar la media y el conteo del Item
			$this->updateItemAverages($voteData['item_id'], $voteData['score']);

			return $vote;
		});
	}

	/**
	 * Función auxiliar para recalcular la media matemática de forma eficiente.
	 */
	private function updateItemAverages(string $itemId, int $newScore): void
	{
		$item = Item::lockForUpdate()->find($itemId); // lockForUpdate evita problemas de concurrencia

		$newCount = $item->vote_count + 1;
		// Nueva media = ((Media anterior * Votos anteriores) + Nueva Nota) / Nuevos Votos totales
		$newAvg = (($item->vote_avg * $item->vote_count) + $newScore) / $newCount;

		$item->update([
			'vote_count' => $newCount,
			'vote_avg' => round($newAvg, 2) // Redondeamos a 2 decimales
		]);
	}

	public function changeVote(Vote $vote, int $newScore): Vote
	{
		return DB::transaction(function () use ($vote, $newScore) {
			$oldScore = $vote->score;
			$vote->update(['score' => $newScore]);

			// Actualizar la media del Item restando el voto antiguo y sumando el nuevo
			$item = Item::lockForUpdate()->find($vote->item_id);
			$newAvg = (($item->vote_avg * $item->vote_count) - $oldScore + $newScore) / $item->vote_count;
			$item->update(['vote_avg' => round($newAvg, 2)]);

			return $vote;
		});
	}

	public function deleteVote(Vote $vote): bool
	{
		return DB::transaction(function () use ($vote) {
			$score = $vote->score;
			$item = Item::lockForUpdate()->find($vote->item_id);

			// Actualizar la media del Item restando el voto eliminado
			if ($item->vote_count > 1) {
				$newAvg = (($item->vote_avg * $item->vote_count) - $score) / ($item->vote_count - 1);
				$item->update(['vote_avg' => round($newAvg, 2)]);
			} else {
				$item->update(['vote_avg' => 0]);
			}

			return $vote->delete();
		});
	}
}