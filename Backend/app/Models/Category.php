<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

		protected $appends = ['items_count'];

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

	// ✅ Accessor para items_count
		protected function itemsCount(): Attribute
		{
			return Attribute::make(
				get: function () {
					// Si ya está cargado con withCount, usar ese valor
					if (isset($this->attributes['items_count'])) {
						return $this->attributes['items_count'];
					}

					// Si la relación items ya está cargada, contar desde ahí
					if ($this->relationLoaded('items')) {
						return $this->items->where('state', Item::STATE_ACCEPTED)->count();
					}

					// Caso contrario, hacer query (solo cuando sea necesario)
					return $this->acceptedItems()->count();
				}
			);
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
