<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'image'
    ];
		protected $casts = [
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];

    // Relations
    // 1 to many Items

		public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

		public function acceptedItems(): HasMany
		{
				return $this->hasMany(Item::class)->where('state', Item::STATE_ACCEPTED);
		}

	public function scopeWithItemCount($query)
	{
		return $query->withCount(['items as items_count' => function ($query) {
			$query->where('state', Item::STATE_ACCEPTED);
		}]);
	}

	public function getRouteKeyName(): string
	{
		return 'slug';
	}
}
