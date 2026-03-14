<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\CategoryService;

class DeleteCategoryAction
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function execute(Category $category): bool
    {
        return $this->categoryService->deleteCategory($category);
    }
}

