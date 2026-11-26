<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa un comentario realizado por un usuario sobre un ítem.
 * Cada comentario pertenece a un ítem y a un usuario, y puede ser ocultado por moderación.
 * Proporciona relaciones con el ítem y con el usuario que lo creó.
 * Desde la F3-02 puede ser una respuesta (`parent_id`, un solo nivel) o una reseña (`vote_id`, ligada a la nota del autor).
 * Desde la F3-08, `user_id` es null si el autor borró su cuenta y el comentario tenía respuestas (anonimizado).
 */
class ItemComment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const REVIEW_DUPLICATE_MESSAGE = 'Ya has escrito una reseña de este ítem.';

    // Texto que sustituye al de un comentario anonimizado al borrar la cuenta de su autor (F3-08).
    public const ANONYMIZED_CONTENT = '[comentario eliminado]';

    protected $attributes = [
        'is_hidden' => false,
    ];

    protected $fillable = [
        'item_id',
        'user_id',
        'content',
        'parent_id',
        'vote_id',
        'is_hidden',
        'hidden_reason',
        'hidden_by',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones

    /**
     * Define la relación de pertenencia con el modelo Item.
     * Permite acceder al ítem al que pertenece este comentario.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Define la relación de pertenencia con el modelo User para el autor del comentario.
     * Permite acceder al usuario que creó este comentario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Comentario al que responde este (F3-02). Es null si es un comentario principal.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Respuestas a este comentario (F3-02, un solo nivel).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Voto del autor al que va ligada la reseña (F3-02). Es null si no es una reseña.
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    /**
     * Oculta el comentario por moderación (lo usan el ocultado manual del admin y los reportes, F3-04).
     */
    public function hideByModerator(string $moderatorId, ?string $reason): void
    {
        $this->update([
            'is_hidden' => true,
            'hidden_reason' => $reason,
            'hidden_by' => $moderatorId,
        ]);
    }
}
