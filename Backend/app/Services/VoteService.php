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
            $updated = $this->changeVote($existingVote, [
                'type' => $voteData['type'] ?? $existingVote->type,
                'score' => array_key_exists('score', $voteData) ? $voteData['score'] : $existingVote->score,
            ]);
            $updated->setAttribute('was_existing', true);

            return $updated;
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
                abort(409, 'Ya has votado este item.');
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

            $newType = $payload['type'] ?? $vote->type;
            $newScore = array_key_exists('score', $payload) ? $payload['score'] : $vote->score;

            if ($newType === Vote::TYPE_VOTE && $newScore === null) {
                abort(422, 'score es obligatorio cuando type es vote.');
            }

            if ($newType === Vote::TYPE_SKIP) {
                $newScore = null;
            }

            $oldType = $vote->type;
            $oldScore = $vote->score;

            $vote->update([
                'type' => $newType,
                'score' => $newScore,
            ]);

            if ($oldType === Vote::TYPE_VOTE && $newType === Vote::TYPE_VOTE) {
                $safeOldScore = (int) $oldScore;
                $safeNewScore = (int) $newScore;
                $newAvg = (($item->vote_avg * $item->vote_count) - $safeOldScore + $safeNewScore) / $item->vote_count;
                $item->update(['vote_avg' => round($newAvg, 2)]);

                return $vote->fresh();
            }

            if ($oldType === Vote::TYPE_SKIP && $newType === Vote::TYPE_SKIP) {
                return $vote->fresh();
            }

            if ($oldType === Vote::TYPE_SKIP && $newType === Vote::TYPE_VOTE) {
                $safeNewScore = (int) $newScore;
                $newCount = $item->vote_count + 1;
                $newAvg = (($item->vote_avg * $item->vote_count) + $safeNewScore) / $newCount;

                $voteOwner = User::lockForUpdate()->findOrFail($vote->user_id);

                $this->kudosService->awardIfFirst(
                    user: $voteOwner,
                    kudosAmount: KudosRules::rewardForVoteFirstTimeItem(),
                    reason: KudosRules::reasonForVoteFirstTimeItem(),
                    actionKey: KudosRules::actionKeyForVoteFirstTimeItem($vote->user_id, $vote->item_id),
                    referenceType: Item::class,
                    referenceId: $vote->item_id,
                );

                $item->update([
                    'vote_count' => $newCount,
                    'vote_avg' => round($newAvg, 2),
                ]);

                return $vote->fresh();
            }

            // old vote -> new skip
            $safeOldScore = (int) $oldScore;
            if ($item->vote_count > 1) {
                $newCount = $item->vote_count - 1;
                $newAvg = (($item->vote_avg * $item->vote_count) - $safeOldScore) / $newCount;

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
