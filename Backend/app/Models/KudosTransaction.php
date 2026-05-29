<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Representa una transacción de kudos realizada por un usuario.
 * Cada transacción registra la cantidad de kudos otorgados, el motivo, la acción que los generó y una referencia a la entidad relacionada.
 * Proporciona métodos para insertar transacciones de manera segura evitando duplicados.
 */
class KudosTransaction extends Model
{
	use HasUuids;

	protected $fillable = [
		'user_id',
		'kudos_amount',
		'reason',
		'action_key',
		'reference_type',
		'reference_id',
	];

	protected $casts = [
		'kudos_amount' => 'integer',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

    /**
     * Define la relación de pertenencia con el modelo User.
     * Permite acceder al usuario que realizó esta transacción de kudos.
     */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

    /**
     * Define una relación polimórfica que permite asociar esta transacción de kudos con cualquier modelo de referencia (por ejemplo, un ítem, un comentario, etc.).
     * La relación se basa en los campos 'reference_type' y 'reference_id' para determinar el modelo y la instancia a la que se refiere esta transacción.
     */
	public function reference(): MorphTo
	{
		return $this->morphTo();
	}

    /**
     * Inserta una nueva transacción de kudos si no existe una con la misma combinación de usuario, acción y referencia.
     * Esto evita que se dupliquen transacciones para la misma acción y referencia.
     *
     * @param string $userId El ID del usuario que otorga los kudos.
     * @param int $amount La cantidad de kudos otorgados.
     * @param string $reason El motivo por el cual se otorgan los kudos.
     * @param string $actionKey Una clave que identifica la acción que generó los kudos (por ejemplo, 'vote', 'comment', etc.).
     * @param string $referenceType El tipo de entidad a la que se refiere esta transacción (por ejemplo, 'item', 'comment', etc.).
     * @param string $referenceId El ID de la entidad a la que se refiere esta transacción.
     * @return bool Retorna true si se insertó una nueva transacción, o false si ya existía una con la misma combinación.
     */
	public static function insertIfNotExists(
		string $userId,
		int    $amount,
		string $reason,
		string $actionKey,
		string $referenceType,
		string $referenceId
	): bool
	{
		$inserted = self::query()->insertOrIgnore([
			'id' => (string)Str::uuid(),
			'user_id' => $userId,
			'kudos_amount' => $amount,
			'reason' => $reason,
			'action_key' => $actionKey,
			'reference_type' => $referenceType,
			'reference_id' => $referenceId,
			'created_at' => now(),
			'updated_at' => now(),
		]);

		return $inserted > 0;
	}
}
