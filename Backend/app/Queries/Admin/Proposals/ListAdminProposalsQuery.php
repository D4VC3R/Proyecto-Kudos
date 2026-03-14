<?php

namespace App\Queries\Admin\Proposals;

use App\Services\ProposalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminProposalsQuery
{
    public function __construct(protected ProposalService $proposalService)
    {
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->proposalService->getForAdmin($filters, $perPage);
    }
}

