<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Repositories\VoteRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VoteService
{
    public function __construct(
        protected VoteRepository $voteRepository,
        protected KudosService $kudosService,
    ) {
    }

    public function emitVote(User $user, array $voteData): Vote
    {
        $existingVote = $this->voteRepository->findByUserAndItem($user->id, $voteData['item_id']);
        if ($existingVote) {
            $existingVote->setAttribute('was_existing', true);

            return $existingVote;
        }

        $item = Item::findOrFail($voteData['item_id']);
        if ($item->status !== Item::STATUS_ACTIVE) {
            abort(422, 'No se puede votar un item inactivo.');
        }

        return DB::transaction(function () use ($user, $voteData) {
            $voteData['user_id'] = $user->id;
            $voteData['type'] = $voteData['type'] ?? Vote::TYPE_VOTE;
            try {
                $vote = $this->voteRepository->create($voteData);
            } catch (QueryException $e) {
                $raceVote = $this->voteRepository->findByUserAndItem($user->id, $voteData['item_id']);
                if ($raceVote) {
                    $raceVote->setAttribute('was_existing', true);

                    return $raceVote;
                }

                throw $e;
            }

            if ($voteData['type'] === Vote::TYPE_VOTE) {
                $this->kudosService->awardIfFirst(
                    user: $user,
                    kudosAmount: KudosRules::rewardForVoteFirstTimeItem(),
                    reason: KudosRules::reasonForVoteFirstTimeItem(),
                    actionKey: KudosRules::actionKeyForVoteFirstTimeItem($user->id, $voteData['item_id']),
                    referenceType: Item::class,
                    referenceId: $voteData['item_id'],
                );

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

    public function changeVote(Vote $vote, array $payload): Vote
    {
        return DB::transaction(function () use ($vote, $payload) {
            $item = Item::lockForUpdate()->findOrFail($vote->item_id);

            if ($item->status !== Item::STATUS_ACTIVE) {
                abort(422, 'No se puede actualizar el voto porque el item esta inactivo.');
            }

            if ($vote->type !== Vote::TYPE_VOTE) {
                abort(422, 'Solo se puede editar la puntuacion de una votacion emitida.');
            }

            $newScore = (int) ($payload['score'] ?? $vote->score);

            $oldScore = (int) $vote->score;

            $vote->update(['score' => $newScore]);

            $newAvg = (($item->vote_avg * $item->vote_count) - $oldScore + $newScore) / $item->vote_count;
            $item->update(['vote_avg' => round($newAvg, 2)]);


            return $vote->fresh();
        });
    }

    public function deleteVote(Vote $vote): bool
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
