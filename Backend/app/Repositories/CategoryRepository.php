<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Collection;

class CategoryRepository
{

	public function getAllOrdered(): Collection
	{
		return Category::orderBy('name')->get();
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
		return $category->load([
			'items' => function ($query) {
				$query->where('state', Item::STATE_ACCEPTED)
					->with(['creator:id,name', 'tags:id,name'])
					->latest();
			}
		]);
	}

	public function getItemsRanking(Category $category): Collection
	{
		return $category->items()
			->where('state', Item::STATE_ACCEPTED)
			->with('creator:id,name')
			->orderByDesc('vote_avg')
			->orderByDesc('vote_count')
			->orderBy('name')
			->get();
	}
}