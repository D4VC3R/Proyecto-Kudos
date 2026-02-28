<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategoryService
{
	protected CategoryRepository $categoryRepository;

	public function __construct(CategoryRepository $categoryRepository)
	{
		$this->categoryRepository = $categoryRepository;
	}

	public function getAllCategories(): Collection
	{
		return $this->categoryRepository->getAllOrdered();
	}

	public function createCategory(array $data): Category
	{
		$categoryData = [
			'id' => Str::uuid(),
			'name' => $data['name'],
			'description' => $data['description'],
			'slug' => $data['slug'],
			'image' => $data['image'] ?? null,
		];

		return $this->categoryRepository->create($categoryData);
	}
	public function updateCategory(Category $category, array $data): Category
	{
		return $this->categoryRepository->update($category, $data);
	}
	public function deleteCategory(Category $category): bool
	{
		// Validación de negocio: no eliminar si tiene items
		if ($this->categoryRepository->hasItems($category)) {
			$itemsCount = $this->categoryRepository->getItemsCount($category);
			throw new Exception(
				"No se puede eliminar la categoría porque tiene {$itemsCount} items asociados.",
				409
			);
		}

		return $this->categoryRepository->delete($category);
	}
	public function getCategoryWithItems(Category $category): Category
	{
		return $this->categoryRepository->loadAcceptedItems($category);
	}
	public function getCategoryRanking(Category $category): Collection
	{
		return $this->categoryRepository->getItemsRanking($category);
	}
}