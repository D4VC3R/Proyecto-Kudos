<?php

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Representa un voto o una omisión de voto (skip) de un usuario sobre un ítem.
 * Cada voto tiene un tipo (vote o skip) y una puntuación asociada (en caso de ser un voto).
 * Proporciona relaciones con el usuario que realizó el voto, el ítem votado y las transacciones de kudos asociadas.
 */
class Vote extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_VOTE = 'vote';
    public const TYPE_SKIP = 'skip';

    protected $fillable = [
        'user_id',
        'item_id',
        'type',
        'score',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

	public function scopeApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
			->when(!empty($filters['category_slug']), function ($q) use ($filters) {
				$q->whereHas('item.category', fn($catQuery) => $catQuery->where('slug', $filters['category_slug']));
			})
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$q->whereHas('item', fn($itemQuery) => $itemQuery->where('name', 'ilike', "%{$filters['search']}%"));
			});
	}

    public function kudosTransactions(): MorphMany
    {
        return $this->morphMany(KudosTransaction::class, 'reference');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
