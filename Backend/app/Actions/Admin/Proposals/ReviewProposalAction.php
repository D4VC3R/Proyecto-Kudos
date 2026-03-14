<?php

namespace App\Actions\Admin\Proposals;

use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalService;

class ReviewProposalAction
{
    public function __construct(protected ProposalService $proposalService)
    {
    }

    public function execute(Proposal $proposal, User $admin, string $status, ?string $adminNotes): Proposal
    {
        return $this->proposalService->review($proposal, $admin, $status, $adminNotes);
    }
}

