<?php

namespace App\Models;

use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vote extends Model
{
    use HasFactory, HasUuids;

		protected $fillable = [
			'user_id',
			'item_id',
			'score'
		];

		public function kudosTransactions():MorphMany
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
