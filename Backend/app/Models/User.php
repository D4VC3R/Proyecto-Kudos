<?php

namespace App\Models;

/**
 * Modelo User:
 * Los usuarios tienen dos roles, 'user' o 'admin', roles que se manejan con Spatie.
 * Un user puede ganar puntos Kudos votando elementos (ítems) de distintas categorías
 * y creando nuevos ítems si un usuario administrador los acepta.
 *
 *  */

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'total_kudos' => 0,
        'creations_accepted' => 0,
        'login_streak_count' => 0,
        'max_login_streak_count' => 0,
        'is_banned' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'total_kudos' => 'integer',
            'creations_accepted' => 'integer',
            'login_streak_count' => 'integer',
            'max_login_streak_count' => 'integer',
            'last_login_streak_date' => 'date',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
            'banned_until' => 'datetime',
        ];
    }

    public function isCurrentlyBanned(): bool
    {
        if (!$this->is_banned) {
            return false;
        }

        return $this->banned_until === null || $this->banned_until->isFuture();
    }

	public function scopeAdminApplyFilters($query, array $filters)
	{
		$now = now();

		return $query
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$q->where(fn($sub) => $sub->where('name', 'ilike', "%{$filters['search']}%")
					->orWhere('email', 'ilike', "%{$filters['search']}%"));
			})
			->when(isset($filters['is_banned']), fn($q) => $q->where('is_banned', filter_var($filters['is_banned'], FILTER_VALIDATE_BOOLEAN)))
			->when(!empty($filters['ban_state']), function ($q) use ($filters, $now) {
				match ($filters['ban_state']) {
					'temporary' => $q->where('is_banned', true)->whereNotNull('banned_until')->where('banned_until', '>', $now),
					'permanent' => $q->where('is_banned', true)->whereNull('banned_until'),
					'expired'   => $q->where('is_banned', true)->whereNotNull('banned_until')->where('banned_until', '<=', $now),
					'active'    => $q->where(fn($inner) => $inner->where('is_banned', false)
						->orWhere(fn($expired) => $expired->where('is_banned', true)->whereNotNull('banned_until')->where('banned_until', '<=', $now))),
					default => null,
				};
			})
			->when(!empty($filters['role']), fn($q) => $q->role($filters['role']));
	}

	public function scopeAdminApplySorting($query, array $filters)
	{
		$sortBy = in_array($filters['sort_by'] ?? null, ['name', 'email', 'role', 'status', 'created_at'], true) ? $filters['sort_by'] : 'name';
		$sortDirection = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

		match ($sortBy) {
			'email' => $query->orderByRaw("lower(users.email) {$sortDirection}"),
			'status' => $query->orderByRaw(
				"CASE 
                    WHEN is_banned = false THEN 'activo' 
                    WHEN banned_until IS NULL THEN 'baneado_permanente' 
                    ELSE 'baneado_temporal' 
                END {$sortDirection}"
			),
			'role' => $query->orderByRaw(
				"(SELECT MIN(r.name) FROM model_has_roles AS mhr INNER JOIN roles AS r ON r.uuid = mhr.role_id WHERE mhr.model_id = users.id AND mhr.model_type = ?) {$sortDirection}",
				[self::class]
			),
			'created_at' => $query->orderBy('created_at', $sortDirection),
			default => $query->orderBy('name', $sortDirection),
		};

		if ($sortBy !== 'created_at') $query->orderByDesc('created_at');

		return $query->orderBy('id');
	}

	public static function getAdminSummary(): array
	{
		$now = now();
		return [
			'total_users' => self::count(),
			'banned_temporary' => self::where('is_banned', true)->whereNotNull('banned_until')->where('banned_until', '>', $now)->count(),
			'banned_permanent' => self::where('is_banned', true)->whereNull('banned_until')->count(),
			'banned_expired' => self::where('is_banned', true)->whereNotNull('banned_until')->where('banned_until', '<=', $now)->count(),
		];
	}

	public function getProfileStatistics(): array
	{
		// Estadísticas de votos
		$totalVotes = $this->votes()->where('type', 'vote')->count();
		$totalSkips = $this->votes()->where('type', 'skip')->count();
		$averageScore = $this->votes()->where('type', 'vote')->avg('score');

		// Categoría favorita (la más votada positivamente)[cite: 62]
		$favoriteCategoryId = $this->votes()
			->where('type', 'vote')
			->join('items', 'votes.item_id', '=', 'items.id')
			->groupBy('items.category_id')
			->orderByRaw('COUNT(*) DESC')
			->value('items.category_id');

		$favoriteCategoryName = $favoriteCategoryId
			? \App\Models\Category::find($favoriteCategoryId)?->name
			: null;

		return [
			'total_votes' => $totalVotes,
			'total_skips' => $totalSkips,
			'average_score' => $averageScore ? round((float) $averageScore, 1) : null,
			'favorite_category' => $favoriteCategoryName,
			'accepted_proposals' => $this->proposals()->accepted()->count(),
			'total_comments' => $this->comments()->count(),
			'current_login_streak' => $this->login_streak_count,
			'max_login_streak' => $this->max_login_streak_count,
			'total_kudos' => $this->total_kudos,
		];
	}
	/**
	 * Devuelve el paginador del ranking de Kudos global.
	 */
	public static function getRankingPaginator(int $perPage = 10, int $page = 1)
	{
		return self::query()
			->select(['id', 'name', 'total_kudos', 'created_at'])
			->orderByDesc('total_kudos')
			->orderBy('created_at')
			->orderBy('id')
			->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * Calcula la posición (ranking) absoluta de este usuario.
	 */
	public function getKudosRank(): int
	{
		$usersAhead = self::query()
			->where(function ($query) {
				$query->where('total_kudos', '>', $this->total_kudos)
					->orWhere(function ($tieBreaker) {
						$tieBreaker->where('total_kudos', $this->total_kudos)
							->where(function ($sameKudos) {
								$sameKudos->where('created_at', '<', $this->created_at)
									->orWhere(function ($sameTimestamp) {
										$sameTimestamp->where('created_at', $this->created_at)
											->where('id', '<', $this->id);
									});
							});
					});
			})
			->count();

		return $usersAhead + 1;
	}

	public function loadAdminDetails(): self
	{
		return $this->load(['roles:uuid,name', 'profile'])
			->loadCount(['proposals', 'votes', 'comments', 'items', 'reviewedProposals']);
	}

		// Relaciones
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
		public function items(): HasMany
		{
			return $this->hasMany(Item::class, 'creator_id');
		}
		public function proposals(): HasMany
		{
			return $this->hasMany(Proposal::class, 'creator_id');
		}
		public function reviewedProposals(): HasMany
		{
			return $this->hasMany(Proposal::class, 'reviewed_by');
		}
		public function votes(): HasMany
		{
			return $this->hasMany(Vote::class);
		}
		public function kudosTransactions(): HasMany
		{
			return $this->hasMany(KudosTransaction::class);
		}
    public function comments(): HasMany
    {
      return $this->hasMany(ItemComment::class);
    }
}
