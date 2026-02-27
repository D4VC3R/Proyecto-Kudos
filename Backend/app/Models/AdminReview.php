<?php

namespace App\Models;

use Database\Factories\AdminReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminReview extends Model
{
    use HasFactory, HasUuids;

		protected $fillable = [
			'admin_id',
			'item_id',
			'final_state',
			'reject_reason'
		];

		public function admin(): BelongsTo
		{
			return $this->belongsTo(User::class, 'admin_id');
		}

		public function item(): BelongsTo
		{
			return $this->belongsTo(Item::class);
		}

}
