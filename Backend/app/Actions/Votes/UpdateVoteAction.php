<?php

namespace App\Actions\Votes;

use App\Models\Vote;
use App\Services\VoteService;

class UpdateVoteAction
{
    public function __construct(protected VoteService $voteService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(Vote $vote, array $payload): Vote
    {
        return $this->voteService->changeVote($vote, $payload);
    }
}

