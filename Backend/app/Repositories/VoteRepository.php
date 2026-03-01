<?php

namespace App\Repositories;

use App\Models\Vote;

class VoteRepository
{
	/**
	 * Comprueba si un usuario ya ha votado un ítem concreto.
	 */
	public function findByUserAndItem(string $userId, string $itemId): ?Vote
	{
		return Vote::where('user_id', $userId)
			->where('item_id', $itemId)
			->first();
	}

	/**
	 * Inserta un nuevo voto en la base de datos.
	 */
	public function create(array $data): Vote
	{
		return Vote::create($data);
	}
}