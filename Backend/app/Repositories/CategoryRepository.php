<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Collection;

class CategoryRepository
{

    public function getAllOrdered(): Collection
    {
        return Category::withItemCount()
            ->orderBy('name')
            ->get();
    }

	public function create(array $data): Category
	{
		return Category::create($data);
	}

	public function update(Category $category, array $data): Category
	{
		$category->update($data);
		return $category->fresh();
	}

	public function delete(Category $category): bool
	{
		return $category->delete();
	}

	public function hasItems(Category $category): bool
	{
		return $category->items()->exists();
	}

	public function getItemsCount(Category $category): int
	{
		return $category->items()->count();
	}

	public function loadAcceptedItems(Category $category): Category
	{
        $category->loadCount(['items as items_count' => function ($query) {
            $query->where('status', Item::STATUS_ACTIVE);
        }]);

        return $category->load([
            'fieldDefinitions',
            'items' => function ($query) {
                $query->where('status', Item::STATUS_ACTIVE)
                    ->with(['creator:id,name'])
                    ->inRandomOrder()
                    ->take(30);
            }
        ]);
	}

	public function getItemsRanking(Category $category, int $perPage = 10)
	{
		return $category->items()
			->where('status', Item::STATUS_ACTIVE)
			->with('creator:id,name')
			->orderByDesc('vote_avg')
			->orderByDesc('vote_count')
			->orderBy('name')
			->paginate($perPage);
	}
}
