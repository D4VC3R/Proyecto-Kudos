<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Item;
use App\Models\Proposal;

class AdminRepository
{
    /**
     * Obtiene usuarios paginados con filtros de administración.
     *
     * @param array{search?: ?string, is_banned?: mixed, ban_state?: ?string, role?: ?string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateUsers(array $filters, int $perPage = 20): LengthAwarePaginator
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
        return $query->paginate($safePerPage);
    }

    /**
     * Devuelve el resumen de usuarios para administración.
     *
     * @return array<string,int>
     */
    public function usersSummary(): array
    {
        $now = now();
        return [
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
    }

    /**
     * Obtiene items paginados con filtros de administración.
     *
     * @param array{status?: ?string, category_id?: ?string, creator_id?: ?string, search?: ?string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateItems(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Item::query()->with(['category:id,name,slug', 'creator:id,name,email', 'tags:id,name']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        $safePerPage = min(max($perPage, 1), 100);
        return $query->orderByDesc('created_at')->paginate($safePerPage);
    }

    /**
     * Obtiene propuestas paginadas con filtros de administración.
     *
     * @param array<string,mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateProposals(array $filters, int $perPage = 15): LengthAwarePaginator
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
}

