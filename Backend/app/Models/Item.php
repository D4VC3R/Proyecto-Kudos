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

    // Filtrado y ordenamiento

    /*
     * Aplica filtros a la consulta de ítems según los parámetros proporcionados.
     * Permite filtrar por categoría (ID o slug), búsqueda por nombre y exclusión de ítems votados por un usuario específico.
     */
	public function scopeApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['category_slug']), fn($q) => $q->whereHas('category', fn($qCat) => $qCat->where('slug', $filters['category_slug'])))
			->when(!empty($filters['search']), fn($q) => $q->where('name', 'ilike', "%{$filters['search']}%"))
			->when(!empty($filters['exclude_voted_by']), fn($q) => $q->whereDoesntHave('votes', fn($v) => $v->where('user_id', $filters['exclude_voted_by'])));
	}

    /*
     * Para ordenar la consulta de ítems según los parámetros proporcionados.
     * Permite ordenar por fecha de creación, nombre, promedio de votos o de forma aleatoria.
     */
	public function scopeApplySorting($query, string $sortBy = 'vote_avg', string $sortOrder = 'desc')
	{
		return match ($sortBy) {
			'recent' => $query->orderBy('created_at', $sortOrder)->orderBy('id', 'desc'),
			'name' => $query->orderBy('name', $sortOrder)->orderBy('id', 'asc'),
			'random' => $query->inRandomOrder(),
			default => $query->orderBy('vote_avg', $sortOrder)->orderBy('vote_count', $sortOrder)->orderBy('id', 'desc'),
		};
	}

    /*
     * Aplica filtros específicos para el panel de administración.
     * Permite filtrar por estado, categoría, creador y búsqueda por nombre.
     */
	public function scopeAdminApplyFilters($query, array $filters)
	{
		return $query
			->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
			->when(!empty($filters['category_id']), fn($q) => $q->where('category_id', $filters['category_id']))
			->when(!empty($filters['creator_id']), fn($q) => $q->where('creator_id', $filters['creator_id']))
			->when(!empty($filters['search']), fn($q) => $q->where('name', 'ilike', '%' . $filters['search'] . '%'));
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
}
