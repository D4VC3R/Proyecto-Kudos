<?php

namespace App\Repositories;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProposalRepository
{
    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return Proposal::query()
            ->where('status', Proposal::STATUS_PENDING)
            ->with(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    public function getByUser(User $user): Collection
    {
        return Proposal::query()
            ->where('creator_id', $user->id)
            ->with(['category:id,name,slug', 'reviewer:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Proposal
    {
        return Proposal::create($data);
    }

    public function update(Proposal $proposal, array $data): Proposal
    {
        $proposal->update($data);
        return $proposal->fresh(['creator:id,name', 'category:id,name,slug', 'reviewer:id,name']);
    }

    public function delete(Proposal $proposal): bool
    {
        return $proposal->delete();
    }
}
