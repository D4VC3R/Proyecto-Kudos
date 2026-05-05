<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class KudosTransaction extends Model
{
	use HasFactory, HasUuids;

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

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function reference(): MorphTo
	{
		return $this->morphTo();
	}

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
