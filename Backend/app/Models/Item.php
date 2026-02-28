<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Item extends Model
{
    use HasFactory, HasUuids;

	const STATE_PENDING = 'pending';
	const STATE_ACCEPTED = 'accepted';  // ⬅️ CAMBIADO de APPROVED a ACCEPTED
	const STATE_REJECTED = 'rejected';

	protected $fillable = [
		'name',
		'description',
		'image',
		'state',
		'locked_at',
		'locked_by_admin_id',
		'vote_avg',
		'vote_count',
		'creator_id',
		'category_id',
	];

	protected $casts = [
		'vote_avg' => 'float',
		'vote_count' => 'integer',
		'locked_at' => 'datetime',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

    // Relaciones
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    public function lockedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_admin_id');
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'item_tag');
    }
		public function votes(): HasMany
		{
			return $this->hasMany(Vote::class);
		}

		public function adminReviews(): HasMany
		{
			return $this->hasMany(AdminReview::class);
		}
		public function kudosTransactions():MorphMany
		{
			return $this->morphMany(KudosTransaction::class, 'reference');
		}

	// Scopes útiles
	public function scopeAccepted($query)
	{
		return $query->where('state', self::STATE_ACCEPTED);
	}

	public function scopePending($query)
	{
		return $query->where('state', self::STATE_PENDING);
	}

	public function scopeRejected($query)
	{
		return $query->where('state', self::STATE_REJECTED);
	}
}
