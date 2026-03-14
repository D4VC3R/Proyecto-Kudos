<?php

namespace App\Actions\Votes;

use App\Models\User;
use App\Models\Vote;
use App\Services\VoteService;

class EmitVoteAction
{
    public function __construct(protected VoteService $voteService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(User $user, array $payload): Vote
    {
        return $this->voteService->emitVote($user, $payload);
    }
}

