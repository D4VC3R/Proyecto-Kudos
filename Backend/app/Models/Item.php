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

    protected $fillable = [
        'name',
        'description',
        'image',
        'category_id',
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
        return $this->belongsToMany(Tag::class);
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
}
