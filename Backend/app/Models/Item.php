<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
	use HasFactory, HasUuids, SoftDeletes;

	public const STATUS_ACTIVE = 'active';
	public const STATUS_INACTIVE = 'inactive';

	protected $attributes = [
		'status' => self::STATUS_ACTIVE,
		'vote_avg' => 0,
		'vote_count' => 0,
	];

	protected $fillable = [
		'name',
		'description',
		'images',
		'status',
		'vote_avg',
		'vote_count',
		'creator_id',
		'category_id',
	];

	protected $casts = [
		'images' => 'array',
		'vote_avg' => 'float',
		'vote_count' => 'integer',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
	];

	public function scopeApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['category_slug']), fn($q) => $q->whereHas('category', fn($qCat) => $qCat->where('slug', $filters['category_slug'])))
			->when(!empty($filters['search']), fn($q) => $q->where('name', 'ilike', "%{$filters['search']}%"))
			->when(!empty($filters['exclude_voted_by']), fn($q) => $q->whereDoesntHave('votes', fn($v) => $v->where('user_id', $filters['exclude_voted_by'])));
	}

	public function scopeApplySorting($query, string $sortBy = 'vote_avg', string $sortOrder = 'desc')
	{
		return match ($sortBy) {
			'recent' => $query->orderBy('created_at', $sortOrder)->orderBy('id', 'desc'),
			'name' => $query->orderBy('name', $sortOrder)->orderBy('id', 'asc'),
			'random' => $query->inRandomOrder(),
			default => $query->orderBy('vote_avg', $sortOrder)->orderBy('vote_count', $sortOrder)->orderBy('id', 'desc'),
		};
	}

	public function scopeAdminApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['creator_id']), fn($q) => $q->where('creator_id', $filters['creator_id']))
			->when(!empty($filters['search']), fn($q) => $q->where('name', 'ilike', '%' . $filters['search'] . '%'));
	}

	public function scopeAdminApplySorting($query, string $sortBy = 'created_at', string $sortDirection = 'desc')
	{
		$direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

		match ($sortBy) {
			'name' => $query->orderBy('name', $direction),
			'status' => $query->orderByRaw(
				"CASE WHEN status = 'active' THEN 0 ELSE 1 END {$direction}"
			),
			default => $query->orderBy('created_at', $direction),
		};

		if ($sortBy !== 'created_at') {
			$query->orderByDesc('created_at');
		}

		return $query->orderBy('id');
	}

	public function creator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'creator_id');
	}

	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class);
	}


	public function votes(): HasMany
	{
		return $this->hasMany(Vote::class);
	}

	public function userVote(): HasOne
	{
		return $this->hasOne(Vote::class);
	}

	public function comments(): HasMany
	{
		return $this->hasMany(ItemComment::class);
	}

	public function kudosTransactions(): MorphMany
	{
		return $this->morphMany(KudosTransaction::class, 'reference');
	}

	public function scopeActive($query)
	{
		return $query->where('status', self::STATUS_ACTIVE);
	}

	public function scopeInactive($query)
	{
		return $query->where('status', self::STATUS_INACTIVE);
	}
}
