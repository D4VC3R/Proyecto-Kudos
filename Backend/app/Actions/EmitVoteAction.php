<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\KudosService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EmitVoteAction
{
	public function __construct(protected KudosService $kudosService) {}

	public function execute(User $user, array $voteData): Vote
	{
		$existingVote = Vote::where('user_id', $user->id)->where('item_id', $voteData['item_id'])->first();
		if ($existingVote) {
			$existingVote->setAttribute('was_existing', true);
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
					return $raceVote;
				}
				throw $e;
			}

			if ($voteData['type'] === Vote::TYPE_VOTE) {
				$this->kudosService->processFirstTimeVote($user, $voteData['item_id']);

				$this->updateItemAverages($voteData['item_id'], (int) $voteData['score']);
			}

			$vote->setAttribute('was_existing', false);
			return $vote;
		});
	}

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