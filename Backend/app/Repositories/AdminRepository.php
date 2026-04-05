<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Item;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminRepository
{
    /**
     * Obtiene usuarios paginados con filtros de administración.
     *
     * @param array{search?: ?string, is_banned?: mixed, ban_state?: ?string, role?: ?string, sort_by?: ?string, sort_direction?: ?string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateUsers(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query()->with('roles:uuid,name');
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

        $this->applyUsersSorting($query, $filters);

        $safePerPage = min(max($perPage, 1), 100);
        return $query->paginate($safePerPage);
    }

    /**
     * Aplica ordenación server-side al listado de usuarios admin.
     *
     * @param Builder<User> $query
     * @param array<string,mixed> $filters
     */
    private function applyUsersSorting(Builder $query, array $filters): void
    {
        $sortBy = in_array($filters['sort_by'] ?? null, ['name', 'email', 'role', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'name';

        $sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $normalizedName = $this->normalizedTextExpression('users.name');

        match ($sortBy) {
            'email' => $query->orderByRaw("lower(users.email) {$sortDirection}"),
            'role' => $this->applyUsersRoleSorting($query, $sortDirection),
            'status' => $query->orderByRaw(
                "CASE
                    WHEN is_banned = false THEN 'activo'
                    WHEN banned_until IS NULL THEN 'baneado_permanente'
                    ELSE 'baneado_temporal'
                END {$sortDirection}"
            ),
            'created_at' => $query->orderBy('created_at', $sortDirection),
            default => $query->orderByRaw("{$normalizedName} {$sortDirection}"),
        };

        // Empates estables para evitar cambios de orden entre páginas.
        if ($sortBy !== 'created_at') {
            $query->orderByDesc('created_at');
        }

        $query->orderBy('id');
    }

    /**
     * Devuelve una expresion SQL para ordenar texto ignorando tildes y mayusculas.
     */
    private function normalizedTextExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "translate(lower({$column}), 'áàäâãåéèëêíìïîóòöôõúùüûçñ', 'aaaaaaeeeeiiiiooooouuuucn')";
        }

        $replacements = [
            'á' => 'a', 'Á' => 'a',
            'é' => 'e', 'É' => 'e',
            'í' => 'i', 'Í' => 'i',
            'ó' => 'o', 'Ó' => 'o',
            'ú' => 'u', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n',
        ];

        $expression = "lower({$column})";

        foreach ($replacements as $from => $to) {
            $expression = "replace({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }

    /**
     * Ordena por el primer rol alfabético del usuario (si existe).
     *
     * @param Builder<User> $query
     */
    private function applyUsersRoleSorting(Builder $query, string $sortDirection): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';

        $query->orderByRaw(
            "(
                SELECT MIN(r.name)
                FROM {$modelHasRolesTable} AS mhr
                INNER JOIN {$rolesTable} AS r ON r.uuid = mhr.{$rolePivotKey}
                WHERE mhr.{$modelMorphKey} = users.id
                    AND mhr.model_type = ?
            ) {$sortDirection}",
            [User::class]
        );
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

    public function findUserDetail(string $userId): ?User
    {
        return User::query()
            ->with(['roles:uuid,name', 'profile'])
            ->withCount(['proposals', 'votes', 'comments', 'items', 'reviewedProposals'])
            ->find($userId);
    }

    /**
     * Obtiene items paginados con filtros de administración.
     *
     * @param array{status?: ?string, category_id?: ?string, creator_id?: ?string, search?: ?string, sort_by?: ?string, sort_direction?: ?string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateItems(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Item::query()->with(['category:id,name,slug', 'creator:id,name,email']);

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

        $this->applyItemsSorting($query, $filters);

        $safePerPage = min(max($perPage, 1), 100);
        return $query->paginate($safePerPage);
    }

    /**
     * Aplica ordenacion server-side al listado de items admin.
     *
     * @param Builder<Item> $query
     * @param array<string,mixed> $filters
     */
    private function applyItemsSorting(Builder $query, array $filters): void
    {
        $sortBy = in_array($filters['sort_by'] ?? null, ['name', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'created_at';

        $sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sortBy) {
            'name' => $query->orderBy('name', $sortDirection),
            'status' => $query->orderByRaw(
                "CASE
                    WHEN status = 'active' THEN 0
                    ELSE 1
                END {$sortDirection}"
            ),
            default => $query->orderBy('created_at', $sortDirection),
        };

        if ($sortBy !== 'created_at') {
            $query->orderByDesc('created_at');
        }

        $query->orderBy('id');
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

