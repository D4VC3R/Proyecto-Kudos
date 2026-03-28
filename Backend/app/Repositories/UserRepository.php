<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserRepository
{
    public function paginateRanking(int $perPage, int $page = 1): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'total_kudos', 'created_at'])
            ->orderByDesc('total_kudos')
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getUserRank(User $user): int
    {
        $usersAhead = User::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('total_kudos', '>', $user->total_kudos)
                    ->orWhere(function (Builder $tieBreaker) use ($user) {
                        $tieBreaker->where('total_kudos', $user->total_kudos)
                            ->where(function (Builder $sameKudos) use ($user) {
                                $sameKudos->where('created_at', '<', $user->created_at)
                                    ->orWhere(function (Builder $sameTimestamp) use ($user) {
                                        $sameTimestamp->where('created_at', $user->created_at)
                                            ->where('id', '<', $user->id);
                                    });
                            });
                    });
            })
            ->count();

        return $usersAhead + 1;
    }
}

