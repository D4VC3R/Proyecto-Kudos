<?php

namespace App\Models;

use App\Models\CategoryFieldDefinition;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
