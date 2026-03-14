<?php

namespace App\Actions\Votes;

use App\Models\Vote;
use App\Services\VoteService;

class UpdateVoteAction
{
    public function __construct(protected VoteService $voteService)
    {
    }

    public function execute(Vote $vote, int $score): Vote
    {
        return $this->voteService->changeVote($vote, $score);
    }
}

