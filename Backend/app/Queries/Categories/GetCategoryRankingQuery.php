<?php

namespace App\Queries\Categories;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Support\Collection;

class GetCategoryRankingQuery
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function execute(Category $category): Collection
    {
        return $this->categoryService->getCategoryRanking($category);
    }
}

