<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Reporte de un contenido (comentario, lista o perfil) hecho por un usuario (F3-04, DSA).
 * `reportable_type` guarda el nombre completo de la clase (sin morph map: Spatie y las notificaciones
 * también guardan el FQCN); la API usa los alias de REPORTABLE_TYPES.
 */
class Report extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    public const ACTION_HIDE = 'hide';

    public const ACTION_DELETE = 'delete';

    public const ACTION_DISMISS = 'dismiss';

    public const ACTIONS = [self::ACTION_HIDE, self::ACTION_DELETE, self::ACTION_DISMISS];

    public const REASONS = ['spam', 'odio', 'acoso', 'ilegal', 'spoiler', 'otro'];

    public const REASON_LABELS = [
        'spam' => 'Spam o publicidad',
        'odio' => 'Discurso de odio',
        'acoso' => 'Acoso',
        'ilegal' => 'Contenido ilegal',
        'spoiler' => 'Spoiler sin avisar',
        'otro' => 'Otro motivo',
    ];

    public const REPORTABLE_TYPES = [
        'comment' => ItemComment::class,
        'list' => UserList::class,
        'user' => User::class,
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'resolved_by',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Carga lo que necesita AdminReportResource (incluye los contenidos con borrado suave).
     */
    public function scopeWithAdminRelations(Builder $query): Builder
    {
        return $query->with([
            'reporter:id,name',
            'resolver:id,name',
            'reportable' => fn (MorphTo $morphTo) => $morphTo->constrain([
                ItemComment::class => fn (Builder $q) => $q->withTrashed()->with(['user:id,name', 'item:id,category_id', 'item.category:id,slug']),
                UserList::class => fn (Builder $q) => $q->withTrashed()->with('user:id,name'),
                User::class => fn (Builder $q) => $q->with('profile:id,user_id,biography,is_public'),
            ]),
        ]);
    }

    /**
     * Alias público del tipo reportado (comment, list o user), o null si no es uno conocido.
     */
    public function reportableAlias(): ?string
    {
        return array_search($this->reportable_type, self::REPORTABLE_TYPES, true) ?: null;
    }

    /**
     * El contenido reportado, o null si ya no existe (los borrados suaves quedan fuera por el scope global).
     */
    public function resolveReportable(): ?Model
    {
        if ($this->reportableAlias() === null) {
            return null;
        }

        return $this->reportable_type::query()->find($this->reportable_id);
    }

    /**
     * Id del autor de un contenido reportable (el propio usuario si se reporta un perfil).
     */
    public static function authorIdOf(Model $reportable): ?string
    {
        return match (true) {
            $reportable instanceof ItemComment, $reportable instanceof UserList => $reportable->user_id,
            $reportable instanceof User => $reportable->id,
            default => null,
        };
    }

    public function reasonLabel(): string
    {
        return self::REASON_LABELS[$this->reason] ?? $this->reason;
    }
}
