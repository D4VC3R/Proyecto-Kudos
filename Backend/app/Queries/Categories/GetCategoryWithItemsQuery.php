<?php

namespace App\Queries\Categories;

use App\Models\Category;
use App\Services\CategoryService;

class GetCategoryWithItemsQuery
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function execute(Category $category): Category
    {
        return $this->categoryService->getCategoryWithItems($category);
    }
}

