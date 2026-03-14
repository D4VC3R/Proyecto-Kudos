<?php

namespace App\Queries\Admin\Proposals;

use App\Services\ProposalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingProposalsQuery
{
    public function __construct(protected ProposalService $proposalService)
    {
    }

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->proposalService->getPending($perPage);
    }
}

