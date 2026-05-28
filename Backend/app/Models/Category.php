<?php

namespace App\Models;

use App\Models\CategoryFieldDefinition;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'image'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(Item::class)->where('status', Item::STATUS_ACTIVE);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }


    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(CategoryFieldDefinition::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    // Accessor para items_count
    protected function itemsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['items_count']
                ?? ($this->relationLoaded('items')
                    ? $this->items->where('status', Item::STATUS_ACTIVE)->count()
                    : $this->activeItems()->count())
        );
    }

    public function scopeWithItemCount($query)
    {
        return $query->withCount(['items as items_count' => function ($query) {
            $query->where('status', Item::STATUS_ACTIVE);
        }]);
    }
    public function loadAcceptedItemsWithDetails(): static
    {
        return $this->load([
            'fieldDefinitions',
            'items' => function ($query) {
                $query->where('status', Item::STATUS_ACTIVE)
                    ->with(['creator:id,name'])
                    ->inRandomOrder()
                    ->take(30);
            }
        ]);
    }

    /**
     * Obtiene el ranking paginado de los ítems de esta categoría.
     */
	public function getRankingPaginator(int $perPage = 10): LengthAwarePaginator
	{
		return $this->items()
			->select([
				'id',
				'category_id',
				'name',
				'vote_avg',
				'vote_count'
			])
			->where('status', Item::STATUS_ACTIVE)
			->orderByDesc('vote_avg')
			->orderByDesc('vote_count')
			->orderBy('name')
			->paginate($perPage);
	}

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
