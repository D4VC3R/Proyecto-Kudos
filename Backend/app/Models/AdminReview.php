<?php

namespace App\Models;

use Database\Factories\AdminReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminReview extends Model
{
    use HasFactory, HasUuids;

		protected $fillable = [
			'admin_id',
			'item_id',
			'final_state',
			'reject_reason'
		];

}
