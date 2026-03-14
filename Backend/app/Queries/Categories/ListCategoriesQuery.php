<?php

namespace App\Queries\Categories;

use App\Services\CategoryService;
use Illuminate\Support\Collection;

class ListCategoriesQuery
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function execute(): Collection
    {
        return $this->categoryService->getAllCategories();
    }
}

