<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class DeleteVoteAction
{
	public function execute(Vote $vote): bool
	{
		return DB::transaction(function () use ($vote) {
			if ($vote->type === Vote::TYPE_SKIP) {
				return (bool) $vote->delete();
			}

			$score = $vote->score;
			$item = Item::lockForUpdate()->findOrFail($vote->item_id);

			if ($item->vote_count > 1) {
				$newCount = $item->vote_count - 1;
				$newAvg = (($item->vote_avg * $item->vote_count) - $score) / $newCount;

				$item->update([
					'vote_count' => $newCount,
					'vote_avg' => round($newAvg, 2),
				]);
			} else {
				$item->update([
					'vote_count' => 0,
					'vote_avg' => 0,
				]);
			}

			return (bool) $vote->delete();
		});
	}
}