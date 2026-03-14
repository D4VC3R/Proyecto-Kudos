<?php

namespace App\Actions\Votes;

use App\Models\Vote;
use App\Services\VoteService;

class DeleteVoteAction
{
    public function __construct(protected VoteService $voteService)
    {
    }

    public function execute(Vote $vote): bool
    {
        return $this->voteService->deleteVote($vote);
    }
}

