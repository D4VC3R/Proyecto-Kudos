<?php

namespace App\Queries\Votes;

use App\Models\User;
use App\Repositories\VoteRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyVotesQuery
{
    public function __construct(protected VoteRepository $voteRepository)
    {
    }

    /**
     * @param array{type?: ?string, category_id?: ?string, search?: ?string} $filters
     */
    public function execute(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->voteRepository->paginateByUser($user, $filters, $perPage);
    }
}

