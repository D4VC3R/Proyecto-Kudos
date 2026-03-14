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

    public function getForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Proposal::query()
            ->with(['creator:id,name,email', 'category:id,name,slug', 'reviewer:id,name']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (!empty($filters['reviewed_by'])) {
            $query->where('reviewed_by', $filters['reviewed_by']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
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
