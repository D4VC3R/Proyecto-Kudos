<?php

namespace App\Queries\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminUsersQuery
{
    /**
     * @param array{search?: ?string, is_banned?: mixed, ban_state?: ?string, role?: ?string} $filters
     * @return array{users: LengthAwarePaginator, summary: array<string,int>}
     */
    public function execute(array $filters, int $perPage = 20): array
    {
        $query = User::query()->with('roles:id,name')->orderByDesc('created_at');
        $now = now();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (($filters['is_banned'] ?? null) !== null) {
            $query->where('is_banned', filter_var($filters['is_banned'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['ban_state'])) {
            $banState = $filters['ban_state'];
            $query->where(function ($q) use ($banState, $now) {
                match ($banState) {
                    'temporary' => $q->where('is_banned', true)
                        ->whereNotNull('banned_until')
                        ->where('banned_until', '>', $now),
                    'permanent' => $q->where('is_banned', true)
                        ->whereNull('banned_until'),
                    'expired' => $q->where('is_banned', true)
                        ->whereNotNull('banned_until')
                        ->where('banned_until', '<=', $now),
                    'active' => $q->where(function ($inner) use ($now) {
                        $inner->where('is_banned', false)
                            ->orWhere(function ($expired) use ($now) {
                                $expired->where('is_banned', true)
                                    ->whereNotNull('banned_until')
                                    ->where('banned_until', '<=', $now);
                            });
                    }),
                    default => null,
                };
            });
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        $safePerPage = min(max($perPage, 1), 100);
        $users = $query->paginate($safePerPage);

        $summary = [
            'total_users' => User::count(),
            'banned_temporary' => User::query()
                ->where('is_banned', true)
                ->whereNotNull('banned_until')
                ->where('banned_until', '>', $now)
                ->count(),
            'banned_permanent' => User::query()
                ->where('is_banned', true)
                ->whereNull('banned_until')
                ->count(),
            'banned_expired' => User::query()
                ->where('is_banned', true)
                ->whereNotNull('banned_until')
                ->where('banned_until', '<=', $now)
                ->count(),
        ];

        return [
            'users' => $users,
            'summary' => $summary,
        ];
    }
}

