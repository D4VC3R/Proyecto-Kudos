<?php

namespace App\Models;

use App\Support\Like;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Representa la interacción de un usuario con un ítem. Tipos:
 * - vote: lo conozco y lo valoro (lleva puntuación).
 * - want: me interesa (wishlist), sin puntuación.
 * - not_interested: no me interesa, sin puntuación.
 * - skip: valor heredado; la API lo normaliza a not_interested.
 * Proporciona relaciones con el usuario que realizó el voto, el ítem votado y las transacciones de kudos asociadas.
 */
class Vote extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_VOTE = 'vote';
    public const TYPE_SKIP = 'skip';
    public const TYPE_WANT = 'want';
    public const TYPE_NOT_INTERESTED = 'not_interested';

    // Tipos que llevan nota y cuentan para las medias y los kudos (F2-01)
    public const RATED_TYPES = [self::TYPE_VOTE];

    // not_interested y su valor heredado skip (la API ya no guarda skip, pero el CHECK lo admite)
    public const NOT_INTERESTED_TYPES = [self::TYPE_NOT_INTERESTED, self::TYPE_SKIP];

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
			->when(!empty($filters['type']), function ($q) use ($filters) {
				// skip es el valor heredado de not_interested: filtrar por cualquiera de los dos devuelve ambos (F2-01)
				if (in_array($filters['type'], self::NOT_INTERESTED_TYPES, true)) {
					$q->whereIn('type', self::NOT_INTERESTED_TYPES);
				} else {
					$q->where('type', $filters['type']);
				}
			})
			->when(!empty($filters['category_slug']), function ($q) use ($filters) {
				$q->whereHas('item.category', fn($catQuery) => $catQuery->where('slug', $filters['category_slug']));
			})
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$pattern = Like::contains($filters['search']);
				$q->whereHas('item', fn($itemQuery) => $itemQuery->where(fn($sub) => $sub->where('name', 'ilike', $pattern)
					->orWhere('original_title', 'ilike', $pattern)));
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
