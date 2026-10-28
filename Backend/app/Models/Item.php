<?php

namespace App\Models;

use App\Services\ItemQualityScorer;
use App\Support\Like;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/*
 * Representa un ítem dentro de una categoría.
 * Cada ítem tiene un creador (usuario), pertenece a una categoría y puede tener múltiples votos y comentarios.
 * Proporciona métodos para aplicar filtros y ordenamientos en las consultas, así como relaciones con otros modelos.
 */
class Item extends Model
{
	use HasFactory, HasUuids, SoftDeletes;

	public const STATUS_ACTIVE = 'active';
	public const STATUS_INACTIVE = 'inactive';

	protected $attributes = [
		'status' => self::STATUS_ACTIVE,
		'vote_avg' => 0,
		'vote_count' => 0,
		'bayes_score' => 0,
		'quality_score' => 0,
	];

	protected $fillable = [
		'name',
		'description',
		'images',
		'status',
		'vote_avg',
		'vote_count',
		'bayes_score',
		'creator_id',
		'category_id',
		'slug',
		'original_title',
		'released_at',
		'attributes',
		'popularity',
		'wikidata_id',
		'description_source',
		'description_url',
		'last_synced_at',
	];

	protected $casts = [
		'images' => 'array',
		'vote_avg' => 'float',
		'vote_count' => 'integer',
		'bayes_score' => 'float',
		'quality_score' => 'integer',
		'attributes' => 'array',
		'released_at' => 'date',
		'last_synced_at' => 'datetime',
		'popularity' => 'float',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
	];

    /*
     * Al crear genera el slug (F1-01) si no viene informado; WithoutModelEvents impide que se ejecute en los seeders.
     * Recalcula quality_score al guardar si cambian los campos que lo determinan (F0-05):
     * imágenes, descripción, released_at o attributes.
     */
	protected static function booted(): void
	{
		static::creating(function (Item $item) {
			if (blank($item->getAttribute('slug'))) {
				$item->setAttribute('slug', static::generateUniqueSlug((string) $item->name, static::slugYear($item)));
			}
		});

		static::saving(function (Item $item) {
			if ($item->isDirty(['images', 'description', 'released_at', 'attributes'])) {
				$item->quality_score = app(ItemQualityScorer::class)->score($item);
			}
		});
	}

    /*
     * Genera un slug único: el nombre en slug más el año, si lo hay, y un sufijo -2, -3… si ya existe.
     * Cuenta también los ítems borrados (el índice único los incluye).
     */
	public static function generateUniqueSlug(string $name, ?int $year = null): string
	{
		$base = rtrim(mb_substr(Str::slug($name), 0, 200), '-');
		if ($base === '') {
			$base = 'item';
		}
		if ($year !== null) {
			$base .= '-' . $year;
		}

		// El slug solo contiene [a-z0-9-], así que el patrón LIKE no necesita escaparse.
		$taken = static::withTrashed()
			->where(fn($q) => $q->where('slug', $base)->orWhere('slug', 'like', $base . '-%'))
			->pluck('slug')
			->all();

		if (!in_array($base, $taken, true)) {
			return $base;
		}

		$suffix = 2;
		while (in_array($base . '-' . $suffix, $taken, true)) {
			$suffix++;
		}

		return $base . '-' . $suffix;
	}

    /*
     * Año para el slug: el de released_at o, si no hay, attributes.year.
     * Se usa getAttribute('attributes') porque $item->attributes es la propiedad interna de Eloquent.
     */
	private static function slugYear(Item $item): ?int
	{
		if ($item->released_at !== null) {
			return (int) $item->released_at->format('Y');
		}

		$attrs = $item->getAttribute('attributes');
		$year = is_array($attrs) ? ($attrs['year'] ?? null) : null;

		return is_numeric($year) ? (int) $year : null;
	}

    // Filtrado y ordenamiento

    /*
     * Aplica filtros a la consulta de ítems según los parámetros proporcionados.
     * Permite filtrar por categoría (ID o slug), búsqueda por nombre o título original y exclusión de ítems votados por un usuario específico.
     * Con 'discoverable' excluye además los ítems con ficha pobre (quality_score por debajo del umbral, F0-05).
     */
	public function scopeApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['category_slug']), fn($q) => $q->whereHas('category', fn($qCat) => $qCat->where('slug', $filters['category_slug'])))
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$pattern = Like::contains($filters['search']);
				$q->where(fn($sub) => $sub->where('name', 'ilike', $pattern)
					->orWhere('original_title', 'ilike', $pattern));
			})
			->when(!empty($filters['exclude_voted_by']), fn($q) => $q->whereDoesntHave('votes', fn($v) => $v->where('user_id', $filters['exclude_voted_by'])))
			->when(!empty($filters['discoverable']), fn($q) => $q->discoverable());
	}

    /*
     * Para ordenar la consulta de ítems según los parámetros proporcionados.
     * Permite ordenar por fecha de creación, nombre, puntuación bayesiana o de forma aleatoria.
     */
	public function scopeApplySorting($query, string $sortBy = 'vote_avg', string $sortOrder = 'desc')
	{
		return match ($sortBy) {
			'recent' => $query->orderBy('created_at', $sortOrder)->orderBy('id', 'desc'),
			'name' => $query->orderBy('name', $sortOrder)->orderBy('id', 'asc'),
			'random' => $query->inRandomOrder(),
			// 'vote_avg' (destacados y "Mejor valorados") ordena por la puntuación bayesiana (F0-04); el parámetro no cambia.
			default => $query->orderBy('bayes_score', $sortOrder)->orderBy('vote_count', $sortOrder)->orderBy('id', 'desc'),
		};
	}

    /*
     * Aplica filtros específicos para el panel de administración.
     * Permite filtrar por estado, categoría, creador y búsqueda por nombre o título original.
     */
	public function scopeAdminApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['creator_id']), fn($q) => $q->where('creator_id', $filters['creator_id']))
			->when(!empty($filters['search']), function ($q) use ($filters) {
				$pattern = Like::contains($filters['search']);
				$q->where(fn($sub) => $sub->where('name', 'ilike', $pattern)
					->orWhere('original_title', 'ilike', $pattern));
			});
	}

    /*
     * Aplica ordenamientos específicos para el panel de administración.
     * Permite ordenar por fecha de creación, nombre o estado.
     */
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

    // Relaciones

    /*
     * Relación de pertenencia con el modelo User, representando al creador del ítem.
     * Permite acceder a los detalles del usuario que creó el ítem.
     */
	public function creator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'creator_id');
	}

    /*
     * Relación de pertenencia con el modelo Category, representando la categoría a la que pertenece el ítem.
     * Permite acceder a los detalles de la categoría asociada al ítem.
     */
	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class);
	}

    /*
     * Relación uno a muchos con el modelo Vote, representando los votos asociados al ítem.
     * Permite acceder a todos los votos realizados sobre el ítem.
     */
	public function votes(): HasMany
	{
		return $this->hasMany(Vote::class);
	}
    /*
     * Relación uno a uno con el modelo Vote, representando el voto específico de un usuario sobre el ítem.
     * Permite acceder al voto realizado por un usuario específico sobre el ítem.
     */
	public function userVote(): HasOne
	{
		return $this->hasOne(Vote::class);
	}

    /*
     * Relación uno a muchos con el modelo ItemComment, representando los comentarios asociados al ítem.
     * Permite acceder a todos los comentarios realizados sobre el ítem.
     */
	public function comments(): HasMany
	{
		return $this->hasMany(ItemComment::class);
	}

    /*
     * Fuentes externas del ítem (TMDB, Wikidata…), F1-01.
     */
	public function sources(): HasMany
	{
		return $this->hasMany(ItemSource::class);
	}

    /*
     * Relación polimórfica uno a muchos con el modelo KudosTransaction, representando las transacciones de kudos asociadas al ítem.
     * Permite acceder a todas las transacciones de kudos relacionadas con el ítem.
     */
	public function kudosTransactions(): MorphMany
	{
		return $this->morphMany(KudosTransaction::class, 'reference');
	}

    /*
     * Scope para filtrar ítems activos.
     * Permite obtener solo los ítems que están en estado activo.
     */
	public function scopeActive($query)
	{
		return $query->where('status', self::STATUS_ACTIVE);
	}

    /*
     * Scope para filtrar ítems inactivos.
     * Permite obtener solo los ítems que están en estado inactivo.
     */
	public function scopeInactive($query)
	{
		return $query->where('status', self::STATUS_INACTIVE);
	}

    /*
     * Scope para filtrar los ítems con ficha suficientemente completa (F0-05).
     * Excluye los que tienen quality_score por debajo de config('catalog.min_quality_for_discovery').
     */
	public function scopeDiscoverable($query)
	{
		return $query->where('quality_score', '>=', (int) config('catalog.min_quality_for_discovery', 40));
	}
}
