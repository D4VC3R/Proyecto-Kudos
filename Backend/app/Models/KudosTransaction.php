<?php

namespace App\Models;


use Database\Factories\KudosTransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KudosTransaction extends Model
{
    use HasFactory, HasUuids;

		protected $fillable = [
			'user_id',
			'kudos_amount',
			'reason'
		];

		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}

		public function reference():MorphTo
		{
			return $this->morphTo();
		}
}
