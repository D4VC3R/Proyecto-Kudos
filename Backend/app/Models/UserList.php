<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Lista de ítems (tabla `lists`; `List` es palabra reservada de PHP).
 * Desde la F2-09 hay listas editoriales (creadas por el admin y mostradas en la portada); con la
 * F3-01 los usuarios crean también las suyas (privadas por defecto) y pueden darles "me gusta".
 */
class UserList extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_UNLISTED = 'unlisted';

    public const VISIBILITY_PRIVATE = 'private';

    protected $table = 'lists';

    protected $attributes = [
        'visibility' => self::VISIBILITY_PRIVATE,
        'is_ranked' => false,
        'is_editorial' => false,
        'items_count' => 0,
        'likes_count' => 0,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'visibility',
        'is_ranked',
        'is_editorial',
        'cover_item_id',
        'items_count',
    ];

    protected $casts = [
        'is_ranked' => 'boolean',
        'is_editorial' => 'boolean',
        'items_count' => 'integer',
        'likes_count' => 'integer',
        'hidden_by_moderation_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // El slug se fija al crear y no cambia al editar el título (URLs estables, igual que en Item).
        static::creating(function (UserList $list) {
            if (empty($list->slug)) {
                $list->slug = static::generateUniqueSlug((string) $list->title);
            }
        });
    }

    /**
     * Genera un slug único: el título en slug y un sufijo -2, -3… si ya existe.
     * Cuenta también las listas borradas (el índice único las incluye).
     */
    public static function generateUniqueSlug(string $title): string
    {
        $base = rtrim(mb_substr(Str::slug($title), 0, 200), '-');
        if ($base === '') {
            $base = 'lista';
        }

        // El slug solo contiene [a-z0-9-], así que el patrón LIKE no necesita escaparse.
        $taken = static::withTrashed()
            ->where(fn ($q) => $q->where('slug', $base)->orWhere('slug', 'like', $base.'-%'))
            ->pluck('slug')
            ->all();

        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $suffix = 2;
        while (in_array($base.'-'.$suffix, $taken, true)) {
            $suffix++;
        }

        return $base.'-'.$suffix;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuarios a los que les gusta la lista (tabla `list_likes`, sin marcas de tiempo de Eloquent).
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'list_likes', 'list_id', 'user_id');
    }

    /**
     * Indica si la moderación ocultó la lista: el dueño no puede volver a publicarla.
     */
    public function isHiddenByModeration(): bool
    {
        return $this->hidden_by_moderation_at !== null;
    }

    /**
     * Entradas de la lista en orden de posición.
     */
    public function listItems(): HasMany
    {
        return $this->hasMany(ListItem::class, 'list_id')->orderBy('position');
    }

    /**
     * Entradas cuyo ítem se puede mostrar: activo, sin borrar y de una categoría activa.
     */
    public function visibleListItems(): HasMany
    {
        return $this->listItems()->whereHas('item', fn ($item) => $item->visible());
    }

    public function scopeEditorial(Builder $query): Builder
    {
        return $query->where('is_editorial', true);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }
}
