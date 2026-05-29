<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa un comentario realizado por un usuario sobre un ítem.
 * Cada comentario pertenece a un ítem y a un usuario, y puede ser ocultado por moderación.
 * Proporciona relaciones con el ítem, el usuario que lo creó y el usuario que lo ocultó, si hay.
 */
class ItemComment extends Model
{
	use  HasUuids, SoftDeletes;

	protected $attributes = [
		'is_hidden' => false,
	];

	protected $fillable = [
		'item_id',
		'user_id',
		'content',
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
     * Define la relación de pertenencia con el modelo User para el moderador que ocultó el comentario.
     * Permite acceder al usuario que ocultó este comentario, si es que fue ocultado.
     */
	public function hiddenByUser(): BelongsTo
	{
		return $this->belongsTo(User::class, 'hidden_by');
	}
}

