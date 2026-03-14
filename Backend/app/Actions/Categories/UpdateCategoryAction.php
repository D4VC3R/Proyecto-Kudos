<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\CategoryService;

class UpdateCategoryAction
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function execute(Category $category, array $payload): Category
    {
        return $this->categoryService->updateCategory($category, $payload);
    }
}

