<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemComment extends Model
{
	use HasFactory, HasUuids, SoftDeletes;

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

	public function item(): BelongsTo
	{
		return $this->belongsTo(Item::class);
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function hiddenByUser(): BelongsTo
	{
		return $this->belongsTo(User::class, 'hidden_by');
	}
}

